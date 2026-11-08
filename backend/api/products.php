<?php
// load db
require_once __DIR__ . '/connectdb.php';

// request method
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$businessId = getActiveBusinessId($pdo);

// editing and deleting are owner/admin only; any member may add
if (in_array($method, ['PUT', 'DELETE'], true)) {
    requireAdmin($pdo);
}

// fetch products
if ($method === 'GET') {
    try {
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $statusFilter = trim($_GET['status'] ?? 'all');
        $category = trim($_GET['category'] ?? '');

        // query base
        $sql = "SELECT * FROM products WHERE business_id = ?";
        $params = [$businessId];

        // search filter
        if ($search !== '') {
            $sql .= " AND (name LIKE ? OR category LIKE ?)";
            $params[] = "%" . like_escape($search) . "%";
            $params[] = "%" . like_escape($search) . "%";
        }

        // category filter
        if ($category !== '') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        // status filter
        if ($statusFilter === 'available') {
            $sql .= " AND status = 'active' AND stock_qty > 10";
        } elseif ($statusFilter === 'low') {
            $sql .= " AND status = 'active' AND stock_qty <= 10";
        } elseif ($statusFilter === 'unavailable' || $statusFilter === 'inactive') {
            $sql .= " AND status = 'inactive'";
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        // format items
        $formatted = array_map(function ($p) {
            $stock = (int)$p['stock_qty'];
            $status = $p['status'];
            $badge = 'status-available';
            $label = 'Available';

            if ($status === 'inactive') {
                $badge = 'status-low';
                $label = 'Unavailable';
            } elseif ($stock <= 10) {
                $badge = 'status-low';
                $label = 'Low stock';
            }

            return [
                'id' => (int)$p['id'],
                'business_id' => (int)$p['business_id'],
                'name' => $p['name'],
                'category' => $p['category'] ?? 'General',
                'price' => (float)$p['price'],
                'stock' => $stock,
                'status' => $status,
                'badge' => $badge,
                'status_label' => $label
            ];
        }, $products);

        // calculate stats
        $cStmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' AND stock_qty > 10 THEN 1 ELSE 0 END) AS available,
                SUM(CASE WHEN status = 'active' AND stock_qty <= 10 THEN 1 ELSE 0 END) AS low_stock,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS unavailable
            FROM products 
            WHERE business_id = ?
        ");
        $cStmt->execute([$businessId]);
        $counts = $cStmt->fetch();

        // top product
        $topStmt = $pdo->prepare("
            SELECT p.name, SUM(s.total) AS total_revenue
            FROM sales s
            JOIN products p ON s.item_id = p.id AND p.business_id = s.business_id
            WHERE s.business_id = ? AND s.item_type = 'product'
            GROUP BY p.id, p.name
            ORDER BY total_revenue DESC
            LIMIT 1
        ");
        $topStmt->execute([$businessId]);
        $topProduct = $topStmt->fetch();

        // products revenue
        $revStmt = $pdo->prepare("
            SELECT COALESCE(SUM(total), 0) AS revenue
            FROM sales
            WHERE business_id = ? AND item_type = 'product'
        ");
        $revStmt->execute([$businessId]);
        $rev = $revStmt->fetch();

        jsonResponse([
            'success' => true,
            'products' => $formatted,
            'counts' => [
                'total' => (int)($counts['total'] ?? 0),
                'available' => (int)($counts['available'] ?? 0),
                'low_stock' => (int)($counts['low_stock'] ?? 0),
                'unavailable' => (int)($counts['unavailable'] ?? 0),
            ],
            'revenue' => (float)($rev['revenue'] ?? 0),
            'top_product' => $topProduct ? [
                'name' => $topProduct['name'],
                'revenue' => (float)$topProduct['total_revenue']
            ] : null
        ]);
    } catch (Exception $e) {
        jsonDbError($e);
    }
}

// create product
if ($method === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? 'create';

    if ($action !== 'create') {
        requireAdmin($pdo);
    }

    // update action
    if ($action === 'update') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) jsonError('Product ID required');

        $name = trim($body['name'] ?? '');
        $category = trim($body['category'] ?? 'General');
        $price = (float)($body['price'] ?? 0);
        $stock = (int)($body['stock'] ?? $body['stock_qty'] ?? 0);
        $status = in_array($body['status'] ?? '', ['active', 'inactive'], true) ? $body['status'] : 'active';

        if ($name === '' || $price < 0 || $stock < 0) {
            jsonError('Valid product name, price and stock are required');
        }
        if (strlen($name) > 255) jsonError('Product name is too long');
        if (strlen($category) > 100) jsonError('Category is too long');
        $category = $category === '' ? 'General' : $category;

        try {
            $check = $pdo->prepare("SELECT id FROM products WHERE id = ? AND business_id = ?");
            $check->execute([$id, $businessId]);
            if (!$check->fetch()) {
                jsonError('Product not found', 404);
            }

            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, category = ?, price = ?, stock_qty = ?, status = ?
                WHERE id = ? AND business_id = ?
            ");
            $stmt->execute([$name, $category, $price, $stock, $status, $id, $businessId]);
            jsonResponse(['success' => true, 'message' => 'Product updated']);
        } catch (Exception $e) {
            jsonDbError($e);
        }
    }

    // delete action
    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) jsonError('Product ID required');

        try {
            $check = $pdo->prepare("SELECT id FROM products WHERE id = ? AND business_id = ?");
            $check->execute([$id, $businessId]);
            if (!$check->fetch()) {
                jsonError('Product not found', 404);
            }

            $saleCheck = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE business_id = ? AND item_type = 'product' AND item_id = ?");
            $saleCheck->execute([$businessId, $id]);
            if ((int)$saleCheck->fetchColumn() > 0) {
                jsonError('Cannot delete a product with sales history. Set it to inactive instead.', 409);
            }

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND business_id = ?");
            $stmt->execute([$id, $businessId]);
            jsonResponse(['success' => true, 'message' => 'Product deleted']);
        } catch (Exception $e) {
            jsonDbError($e);
        }
    }

    if ($action !== 'create') {
        jsonError('Unknown action');
    }

    // add product
    $name = trim($body['name'] ?? '');
    $category = trim($body['category'] ?? 'General');
    $price = (float)($body['price'] ?? 0);
    $stock = (int)($body['stock'] ?? $body['stock_qty'] ?? 0);
    $status = in_array($body['status'] ?? '', ['active', 'inactive'], true) ? $body['status'] : 'active';

    if ($name === '' || $price < 0 || $stock < 0) {
        jsonError('Valid product name, price and stock are required');
    }
    if (strlen($name) > 255) jsonError('Product name is too long');
    if (strlen($category) > 100) jsonError('Category is too long');
    $category = $category === '' ? 'General' : $category;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO products (business_id, name, category, price, stock_qty, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$businessId, $name, $category, $price, $stock, $status]);
        $newId = (int)$pdo->lastInsertId();

        jsonResponse([
            'success' => true,
            'message' => 'Product created',
            'product_id' => $newId
        ], 201);
    } catch (Exception $e) {
        jsonDbError($e);
    }
}

// update put
if ($method === 'PUT') {
    $body = getJsonBody();
    $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) jsonError('Product ID required');

    $name = trim($body['name'] ?? '');
    $category = trim($body['category'] ?? 'General');
    $price = (float)($body['price'] ?? 0);
    $stock = (int)($body['stock'] ?? $body['stock_qty'] ?? 0);
    $status = in_array($body['status'] ?? '', ['active', 'inactive'], true) ? $body['status'] : 'active';

    if ($name === '' || $price < 0 || $stock < 0) {
        jsonError('Valid product name, price and stock are required');
    }
    if (strlen($name) > 255) jsonError('Product name is too long');
    if (strlen($category) > 100) jsonError('Category is too long');
    $category = $category === '' ? 'General' : $category;

    try {
        $check = $pdo->prepare("SELECT id FROM products WHERE id = ? AND business_id = ?");
        $check->execute([$id, $businessId]);
        if (!$check->fetch()) {
            jsonError('Product not found', 404);
        }

        $stmt = $pdo->prepare("
            UPDATE products 
            SET name = ?, category = ?, price = ?, stock_qty = ?, status = ?
            WHERE id = ? AND business_id = ?
        ");
        $stmt->execute([$name, $category, $price, $stock, $status, $id, $businessId]);
        jsonResponse(['success' => true, 'message' => 'Product updated']);
    } catch (Exception $e) {
        jsonDbError($e);
    }
}

// delete request
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        $body = getJsonBody();
        $id = (int)($body['id'] ?? 0);
    }
    if ($id <= 0) jsonError('Product ID required');

    try {
        $check = $pdo->prepare("SELECT id FROM products WHERE id = ? AND business_id = ?");
        $check->execute([$id, $businessId]);
        if (!$check->fetch()) {
            jsonError('Product not found', 404);
        }

        $saleCheck = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE business_id = ? AND item_type = 'product' AND item_id = ?");
        $saleCheck->execute([$businessId, $id]);
        if ((int)$saleCheck->fetchColumn() > 0) {
            jsonError('Cannot delete a product with sales history. Set it to inactive instead.', 409);
        }

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        jsonResponse(['success' => true, 'message' => 'Product deleted']);
    } catch (Exception $e) {
        jsonDbError($e);
    }
}

// invalid method
jsonError('Method not allowed', 405);

