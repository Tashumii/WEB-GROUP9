<?php

namespace App\Controllers\Api;

use App\Exceptions\ApiException;

abstract class CatalogController extends ApiController
{
    /** @return array{table:string,type:string,label:string} */
    abstract protected function catalog(): array;

    public function index()
    {
        $method = $this->method();
        if (! in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
            return $this->jsonError('Method not allowed.', 405);
        }

        return $this->dispatch(function (array $body, ?int $userId, ?int $businessId) use ($method) {
            $catalog = $this->catalog();
            if ($method === 'GET') {
                return $this->listItems($catalog, (int) $businessId);
            }

            $action = $method === 'POST' ? (string) ($body['action'] ?? 'create') : strtolower($method);
            if (($method !== 'POST' && $method !== 'GET') || $action !== 'create') {
                $this->requireAdmin((int) $businessId);
            }

            if ($action === 'create') {
                return $this->createItem($catalog, (int) $businessId, $body);
            }
            if ($action === 'update') {
                $id = $this->inputInt($body, 'id') ?: (int) $this->request->getGet('id');
                return $this->updateItem($catalog, (int) $businessId, $id, $body);
            }
            if ($action === 'delete') {
                $id = $this->inputInt($body, 'id') ?: (int) $this->request->getGet('id');
                return $this->deleteItem($catalog, (int) $businessId, $id);
            }
            throw new ApiException('Unknown action.', 400);
        });
    }

    private function listItems(array $catalog, int $businessId)
    {
        $table = $catalog['table'];
        $type = $catalog['type'];
        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('q') ?? ''));
        $status = trim((string) ($this->request->getGet('status') ?? 'all'));
        $where = ['business_id = ?'];
        $params = [$businessId];

        if ($search !== '') {
            $field = $type === 'product' ? '(name LIKE ? OR category LIKE ?)' : '(name LIKE ? OR description LIKE ?)';
            $escaped = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $where[] = $field;
            $params[] = $escaped;
            $params[] = $escaped;
        }
        if ($type === 'product' && trim((string) $this->request->getGet('category')) !== '') {
            $where[] = 'category = ?';
            $params[] = trim((string) $this->request->getGet('category'));
        }
        if ($type === 'product') {
            if ($status === 'available') {
                $where[] = "status = 'active' AND stock_qty > 10";
            } elseif ($status === 'low') {
                $where[] = "status = 'active' AND stock_qty <= 10";
            } elseif (in_array($status, ['unavailable', 'inactive'], true)) {
                $where[] = "status = 'inactive'";
            }
        } elseif (in_array($status, ['available', 'active'], true)) {
            $where[] = "status = 'active'";
        } elseif (in_array($status, ['unavailable', 'inactive'], true)) {
            $where[] = "status = 'inactive'";
        }

        $rows = $this->db->query('SELECT * FROM ' . $table . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC', $params)->getResultArray();
        if ($type === 'product') {
            $formatted = array_map(static function (array $row): array {
                $stock = (int) $row['stock_qty'];
                $inactive = $row['status'] === 'inactive';
                return [
                    'id' => (int) $row['id'],
                    'business_id' => (int) $row['business_id'],
                    'name' => $row['name'],
                    'category' => $row['category'] ?? 'General',
                    'price' => (float) $row['price'],
                    'stock' => $stock,
                    'status' => $row['status'],
                    'badge' => ($inactive || $stock <= 10) ? 'status-low' : 'status-available',
                    'status_label' => $inactive ? 'Unavailable' : ($stock <= 10 ? 'Low stock' : 'Available'),
                ];
            }, $rows);
            $counts = $this->db->query(
                "SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'active' AND stock_qty > 10 THEN 1 ELSE 0 END) AS available,
                 SUM(CASE WHEN status = 'active' AND stock_qty <= 10 THEN 1 ELSE 0 END) AS low_stock,
                 SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS unavailable FROM products WHERE business_id = ?",
                [$businessId]
            )->getRowArray();
            $top = $this->db->query(
                "SELECT p.name, SUM(s.total) AS total_revenue FROM sales s JOIN products p ON s.item_id = p.id AND p.business_id = s.business_id
                 WHERE s.business_id = ? AND s.item_type = 'product' GROUP BY p.id, p.name ORDER BY total_revenue DESC LIMIT 1",
                [$businessId]
            )->getRowArray();
            $revenue = $this->db->query("SELECT COALESCE(SUM(total), 0) AS revenue FROM sales WHERE business_id = ? AND item_type = 'product'", [$businessId])->getRowArray();
            return $this->json([
                'success' => true,
                'products' => $formatted,
                'counts' => [
                    'total' => (int) ($counts['total'] ?? 0),
                    'available' => (int) ($counts['available'] ?? 0),
                    'low_stock' => (int) ($counts['low_stock'] ?? 0),
                    'unavailable' => (int) ($counts['unavailable'] ?? 0),
                ],
                'revenue' => (float) ($revenue['revenue'] ?? 0),
                'top_product' => $top ? ['name' => $top['name'], 'revenue' => (float) $top['total_revenue']] : null,
            ]);
        }

        $formatted = array_map(static function (array $row): array {
            $isActive = $row['status'] === 'active';
            return [
                'id' => (int) $row['id'],
                'business_id' => (int) $row['business_id'],
                'name' => $row['name'],
                'charged_per' => $row['charged_per'] ?? 'Page',
                'price' => (float) $row['price'],
                'description' => $row['description'] ?? '',
                'status' => $row['status'],
                'badge' => $isActive ? 'status-available' : 'status-low',
                'status_label' => $isActive ? 'Available' : 'Unavailable',
            ];
        }, $rows);
        $counts = $this->db->query(
            "SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS available,
             SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS unavailable FROM services WHERE business_id = ?",
            [$businessId]
        )->getRowArray();
        $top = $this->db->query(
            "SELECT sv.name, SUM(s.total) AS total_revenue FROM sales s JOIN services sv ON s.item_id = sv.id AND sv.business_id = s.business_id
             WHERE s.business_id = ? AND s.item_type = 'service' GROUP BY sv.id, sv.name ORDER BY total_revenue DESC LIMIT 1",
            [$businessId]
        )->getRowArray();
        $revenue = $this->db->query("SELECT COALESCE(SUM(total), 0) AS revenue FROM sales WHERE business_id = ? AND item_type = 'service'", [$businessId])->getRowArray();
        return $this->json([
            'success' => true,
            'services' => $formatted,
            'counts' => [
                'total' => (int) ($counts['total'] ?? 0),
                'available' => (int) ($counts['available'] ?? 0),
                'unavailable' => (int) ($counts['unavailable'] ?? 0),
            ],
            'revenue' => (float) ($revenue['revenue'] ?? 0),
            'top_service' => $top ? ['name' => $top['name'], 'revenue' => (float) $top['total_revenue']] : null,
        ]);
    }

    private function createItem(array $catalog, int $businessId, array $body)
    {
        $data = $this->validatedItem($catalog, $body);
        $data['business_id'] = $businessId;
        $this->db->table($catalog['table'])->insert($data);
        $id = (int) $this->db->insertID();
        $idKey = $catalog['type'] === 'product' ? 'product_id' : 'service_id';
        return $this->json(['success' => true, 'message' => ucfirst($catalog['type']) . ' created', $idKey => $id], 201);
    }

    private function updateItem(array $catalog, int $businessId, int $id, array $body)
    {
        if ($id <= 0) {
            throw new ApiException(ucfirst($catalog['type']) . ' ID required', 400);
        }
        $exists = $this->db->table($catalog['table'])->select('id')->where(['id' => $id, 'business_id' => $businessId])->get()->getRowArray();
        if (! $exists) {
            throw new ApiException(ucfirst($catalog['type']) . ' not found', 404);
        }
        $this->db->table($catalog['table'])->where(['id' => $id, 'business_id' => $businessId])->update($this->validatedItem($catalog, $body));
        return $this->json(['success' => true, 'message' => ucfirst($catalog['type']) . ' updated']);
    }

    private function deleteItem(array $catalog, int $businessId, int $id)
    {
        if ($id <= 0) {
            throw new ApiException(ucfirst($catalog['type']) . ' ID required', 400);
        }
        $exists = $this->db->table($catalog['table'])->select('id')->where(['id' => $id, 'business_id' => $businessId])->get()->getRowArray();
        if (! $exists) {
            throw new ApiException(ucfirst($catalog['type']) . ' not found', 404);
        }
        $salesCount = $this->db->table('sales')->where([
            'business_id' => $businessId,
            'item_type' => $catalog['type'],
            'item_id' => $id,
        ])->countAllResults();
        if ($salesCount > 0) {
            throw new ApiException('Cannot delete a ' . $catalog['type'] . ' with sales history. Set it to inactive instead.', 409);
        }
        $this->db->table($catalog['table'])->where(['id' => $id, 'business_id' => $businessId])->delete();
        return $this->json(['success' => true, 'message' => ucfirst($catalog['type']) . ' deleted']);
    }

    private function validatedItem(array $catalog, array $body): array
    {
        $name = $this->inputString($body, 'name', 256);
        $priceRaw = $body['price'] ?? null;
        $price = filter_var($priceRaw, FILTER_VALIDATE_FLOAT);
        $status = in_array($body['status'] ?? '', ['active', 'inactive'], true) ? $body['status'] : 'active';
        if ($name === '' || $price === false || $price < 0) {
            throw new ApiException($catalog['type'] === 'product' ? 'Valid product name, price and stock are required' : 'Valid service name and price are required', 400);
        }
        if (strlen($name) > 255) {
            throw new ApiException(ucfirst($catalog['type']) . ' name is too long', 400);
        }

        if ($catalog['type'] === 'product') {
            $category = $this->inputString($body, 'category', 101);
            $stock = filter_var($body['stock'] ?? $body['stock_qty'] ?? 0, FILTER_VALIDATE_INT);
            if ($stock === false || $stock < 0) {
                throw new ApiException('Valid product name, price and stock are required', 400);
            }
            if (strlen($category) > 100) {
                throw new ApiException('Category is too long', 400);
            }
            return [
                'name' => $name,
                'category' => $category === '' ? 'General' : $category,
                'price' => $price,
                'stock_qty' => (int) $stock,
                'status' => $status,
            ];
        }

        $chargedPer = $this->inputString($body, 'charged_per', 51);
        if (strlen($chargedPer) > 50) {
            throw new ApiException('Charged per value is too long', 400);
        }
        return [
            'name' => $name,
            'charged_per' => $chargedPer === '' ? 'Page' : $chargedPer,
            'price' => $price,
            'description' => $this->inputString($body, 'description', 10000),
            'status' => $status,
        ];
    }
}
