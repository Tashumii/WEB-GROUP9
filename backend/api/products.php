<?php
// load db
require_once __DIR__ . '/connectdb.php';

// request method
$method = $_SERVER['REQUEST_METHOD'];
$businessId = getActiveBusinessId($pdo);

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
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
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
            JOIN products p ON s.item_id = p.id
            WHERE s.business_id = ? AND s.item_type = 'product'
            GROUP BY p.id, p.name
            ORDER BY total_revenue DESC
            LIMIT 1
        ");
        $topStmt->execute([$businessId]);
        $topProduct = $topStmt->fetch();

        jsonResponse([
            'success' => true,
            'products' => $formatted,
            'counts' => [
                'total' => (int)($counts['total'] ?? 0),
                'available' => (int)($counts['available'] ?? 0),
                'low_stock' => (int)($counts['low_stock'] ?? 0),
                'unavailable' => (int)($counts['unavailable'] ?? 0),
            ],
            'top_product' => $topProduct ? [
                'name' => $topProduct['name'],
                'revenue' => (float)$topProduct['total_revenue']
            ] : null
        ]);
    } catch (Exception $e) {
        jsonError($e->getMessage(), 500);
    }
}

// create product
if ($method === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? 'create';

    // update action
    if ($action === 'update' || isset($body['id']) && $action !== 'create') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) jsonError('Product ID required');

        $name = trim($body['name'] ?? '');
        $category = trim($body['category'] ?? 'General');
        $price = (float)($body['price'] ?? 0);
        $stock = (int)($body['stock'] ?? $body['stock_qty'] ?? 0);
        $status = in_array($body['status'] ?? '', ['active', 'inactive']) ? $body['status'] : 'active';

        try {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, category = ?, price = ?, stock_qty = ?, status = ?
                WHERE id = ? AND business_id = ?
            ");
            $stmt->execute([$name, $category, $price, $stock, $status, $id, $businessId]);
            jsonResponse(['success' => true, 'message' => 'Product updated']);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    // delete action
    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) jsonError('Product ID required');

        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND business_id = ?");
            $stmt->execute([$id, $businessId]);
            jsonResponse(['success' => true, 'message' => 'Product deleted']);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    // add product
    $name = trim($body['name'] ?? '');
    $category = trim($body['category'] ?? 'General');
    $price = (float)($body['price'] ?? 0);
    $stock = (int)($body['stock'] ?? $body['stock_qty'] ?? 0);
    $status = in_array($body['status'] ?? '', ['active', 'inactive']) ? $body['status'] : 'active';

    if ($name === '' || $price < 0) {
        jsonError('Valid product name and price are required');
    }

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
        jsonError($e->getMessage(), 500);
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
    $status = in_array($body['status'] ?? '', ['active', 'inactive']) ? $body['status'] : 'active';

    try {
        $stmt = $pdo->prepare("
            UPDATE products 
            SET name = ?, category = ?, price = ?, stock_qty = ?, status = ?
            WHERE id = ? AND business_id = ?
        ");
        $stmt->execute([$name, $category, $price, $stock, $status, $id, $businessId]);
        jsonResponse(['success' => true, 'message' => 'Product updated']);
    } catch (Exception $e) {
        jsonError($e->getMessage(), 500);
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
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        jsonResponse(['success' => true, 'message' => 'Product deleted']);
    } catch (Exception $e) {
        jsonError($e->getMessage(), 500);
    }
}

// invalid method
jsonError('Method not allowed', 405);

