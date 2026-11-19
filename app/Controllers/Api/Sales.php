<?php

namespace App\Controllers\Api;

use App\Exceptions\ApiException;
use App\Libraries\SalesService;

class Sales extends ApiController
{
    public function index()
    {
        $method = $this->method();
        if ($method === 'POST') {
            return $this->dispatch(function (array $body, ?int $userId, ?int $businessId) {
                return $this->json((new SalesService($this->db))->record((int) $businessId, $userId, $body), 201);
            });
        }
        if ($method !== 'GET') {
            return $this->jsonError('Method not allowed.', 405);
        }

        return $this->dispatch(function (array $body, ?int $userId, ?int $businessId) {
            $range = trim((string) ($this->request->getGet('range') ?? 'all'));
            $dateFrom = trim((string) ($this->request->getGet('date_from') ?? $this->request->getGet('from') ?? ''));
            $dateTo = trim((string) ($this->request->getGet('date_to') ?? $this->request->getGet('to') ?? ''));
            $type = strtolower(trim((string) ($this->request->getGet('type') ?? 'all')));
            $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('q') ?? ''));

            if (($dateFrom !== '' && ! $this->isValidDate($dateFrom)) || ($dateTo !== '' && ! $this->isValidDate($dateTo))) {
                throw new ApiException('Invalid date format', 400);
            }
            if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
                throw new ApiException('date_from cannot be after date_to', 400);
            }

            $where = ['s.business_id = ?'];
            $params = [(int) $businessId];
            if ($range === 'today') {
                $where[] = 'DATE(s.sold_at) = CURDATE()';
            } elseif ($range === 'week') {
                $where[] = 's.sold_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
            } elseif ($range === 'month') {
                $where[] = 'MONTH(s.sold_at) = MONTH(CURDATE()) AND YEAR(s.sold_at) = YEAR(CURDATE())';
            } elseif ($dateFrom !== '' && $dateTo !== '') {
                $where[] = 'DATE(s.sold_at) BETWEEN ? AND ?';
                $params[] = $dateFrom;
                $params[] = $dateTo;
            } elseif ($dateFrom !== '') {
                $where[] = 'DATE(s.sold_at) >= ?';
                $params[] = $dateFrom;
            } elseif ($dateTo !== '') {
                $where[] = 'DATE(s.sold_at) <= ?';
                $params[] = $dateTo;
            }
            if (in_array($type, ['product', 'service'], true)) {
                $where[] = 's.item_type = ?';
                $params[] = $type;
            }

            $sql = "SELECT s.id, s.business_id, s.item_type, s.item_id, s.quantity, s.unit_price, s.total, s.sold_at,
                    CASE WHEN s.item_type = 'product' THEN COALESCE(p.name, 'Unknown Product')
                         WHEN s.item_type = 'service' THEN COALESCE(sv.name, 'Unknown Service') ELSE 'Unknown' END AS item_name
                    FROM sales s LEFT JOIN products p ON s.item_type = 'product' AND s.item_id = p.id AND p.business_id = s.business_id
                    LEFT JOIN services sv ON s.item_type = 'service' AND s.item_id = sv.id AND sv.business_id = s.business_id
                    WHERE " . implode(' AND ', $where);
            if ($search !== '') {
                $sql .= " AND ((s.item_type = 'product' AND p.name LIKE ?) OR (s.item_type = 'service' AND sv.name LIKE ?))";
                $escaped = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
                $params[] = $escaped;
                $params[] = $escaped;
            }
            $rows = $this->db->query($sql . ' ORDER BY s.sold_at DESC, s.id DESC', $params)->getResultArray();

            $formatted = [];
            $total = 0.0;
            $productTotal = 0.0;
            $serviceTotal = 0.0;
            foreach ($rows as $row) {
                $amount = (float) $row['total'];
                $total += $amount;
                if ($row['item_type'] === 'product') {
                    $productTotal += $amount;
                } else {
                    $serviceTotal += $amount;
                }
                $timestamp = strtotime((string) $row['sold_at']);
                $formatted[] = [
                    'id' => (int) $row['id'],
                    'date' => date('M j', $timestamp),
                    'raw_date' => date('Y-m-d', $timestamp),
                    'time' => date('g:iA', $timestamp),
                    'item_name' => $row['item_name'],
                    'type' => ucfirst($row['item_type']),
                    'quantity' => (int) $row['quantity'],
                    'unit_price' => (float) $row['unit_price'],
                    'total' => $amount,
                ];
            }
            $productPct = $total > 0 ? (int) round(($productTotal / $total) * 100) : 0;
            $servicePct = $total > 0 ? 100 - $productPct : 0;
            return $this->json([
                'success' => true,
                'summary' => [
                    'total_amount' => $total,
                    'record_count' => count($formatted),
                    'product_revenue' => $productTotal,
                    'service_revenue' => $serviceTotal,
                    'product_percentage' => $productPct,
                    'service_percentage' => $servicePct,
                    'percentage_label' => "{$productPct}% / {$servicePct}%",
                ],
                'sales' => $formatted,
            ]);
        });
    }
}
