<?php
// load db
require_once __DIR__ . '/connectdb.php';

// request method
$method = $_SERVER['REQUEST_METHOD'];
$businessId = getActiveBusinessId($pdo);

// fetch services
if ($method === 'GET') {
    try {
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $statusFilter = trim($_GET['status'] ?? 'all');

        // query base
        $sql = "SELECT * FROM services WHERE business_id = ?";
        $params = [$businessId];

        // search filter
        if ($search !== '') {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        // status filter
        if ($statusFilter === 'available' || $statusFilter === 'active') {
            $sql .= " AND status = 'active'";
        } elseif ($statusFilter === 'unavailable' || $statusFilter === 'inactive') {
            $sql .= " AND status = 'inactive'";
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $services = $stmt->fetchAll();

        // format items
        $formatted = array_map(function ($s) {
            $isActive = $s['status'] === 'active';
            return [
                'id' => (int)$s['id'],
                'business_id' => (int)$s['business_id'],
                'name' => $s['name'],
                'charged_per' => $s['charged_per'] ?? 'Page',
                'price' => (float)$s['price'],
                'description' => $s['description'] ?? '',
                'status' => $s['status'],
                'badge' => $isActive ? 'status-available' : 'status-low',
                'status_label' => $isActive ? 'Available' : 'Unavailable'
            ];
        }, $services);

        // calculate counts
        $cStmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS available,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS unavailable
            FROM services 
            WHERE business_id = ?
        ");
        $cStmt->execute([$businessId]);
        $counts = $cStmt->fetch();

        // top service
        $topStmt = $pdo->prepare("
            SELECT sv.name, SUM(s.total) AS total_revenue
            FROM sales s
            JOIN services sv ON s.item_id = sv.id
            WHERE s.business_id = ? AND s.item_type = 'service'
            GROUP BY sv.id, sv.name
            ORDER BY total_revenue DESC
            LIMIT 1
        ");
        $topStmt->execute([$businessId]);
        $topService = $topStmt->fetch();

        // services revenue
        $revStmt = $pdo->prepare("
            SELECT SUM(total) AS revenue
            FROM sales
            WHERE business_id = ? AND item_type = 'service'
        ");
        $revStmt->execute([$businessId]);
        $rev = $revStmt->fetch();

        jsonResponse([
            'success' => true,
            'services' => $formatted,
            'counts' => [
                'total' => (int)($counts['total'] ?? 0),
                'available' => (int)($counts['available'] ?? 0),
                'unavailable' => (int)($counts['unavailable'] ?? 0),
            ],
            'revenue' => (float)($rev['revenue'] ?? 0),
            'top_service' => $topService ? [
                'name' => $topService['name'],
                'revenue' => (float)$topService['total_revenue']
            ] : null
        ]);
    } catch (Exception $e) {
        jsonError($e->getMessage(), 500);
    }
}

// create service
if ($method === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? 'create';

    // update action
    if ($action === 'update' || isset($body['id']) && $action !== 'create') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) jsonError('Service ID required');

        $name = trim($body['name'] ?? '');
        $chargedPer = trim($body['charged_per'] ?? 'Page');
        $price = (float)($body['price'] ?? 0);
        $description = trim($body['description'] ?? '');
        $status = in_array($body['status'] ?? '', ['active', 'inactive']) ? $body['status'] : 'active';

        try {
            $stmt = $pdo->prepare("
                UPDATE services 
                SET name = ?, charged_per = ?, price = ?, description = ?, status = ?
                WHERE id = ? AND business_id = ?
            ");
            $stmt->execute([$name, $chargedPer, $price, $description, $status, $id, $businessId]);
            jsonResponse(['success' => true, 'message' => 'Service updated']);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    // delete action
    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) jsonError('Service ID required');

        try {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND business_id = ?");
            $stmt->execute([$id, $businessId]);
            jsonResponse(['success' => true, 'message' => 'Service deleted']);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    // add service
    $name = trim($body['name'] ?? '');
    $chargedPer = trim($body['charged_per'] ?? 'Page');
    $price = (float)($body['price'] ?? 0);
    $description = trim($body['description'] ?? '');
    $status = in_array($body['status'] ?? '', ['active', 'inactive']) ? $body['status'] : 'active';

    if ($name === '' || $price < 0) {
        jsonError('Valid service name and price are required');
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO services (business_id, name, charged_per, price, description, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$businessId, $name, $chargedPer, $price, $description, $status]);
        $newId = (int)$pdo->lastInsertId();

        jsonResponse([
            'success' => true,
            'message' => 'Service created',
            'service_id' => $newId
        ], 201);
    } catch (Exception $e) {
        jsonError($e->getMessage(), 500);
    }
}

// update put
if ($method === 'PUT') {
    $body = getJsonBody();
    $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) jsonError('Service ID required');

    $name = trim($body['name'] ?? '');
    $chargedPer = trim($body['charged_per'] ?? 'Page');
    $price = (float)($body['price'] ?? 0);
    $description = trim($body['description'] ?? '');
    $status = in_array($body['status'] ?? '', ['active', 'inactive']) ? $body['status'] : 'active';

    try {
        $stmt = $pdo->prepare("
            UPDATE services 
            SET name = ?, charged_per = ?, price = ?, description = ?, status = ?
            WHERE id = ? AND business_id = ?
        ");
        $stmt->execute([$name, $chargedPer, $price, $description, $status, $id, $businessId]);
        jsonResponse(['success' => true, 'message' => 'Service updated']);
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
    if ($id <= 0) jsonError('Service ID required');

    try {
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        jsonResponse(['success' => true, 'message' => 'Service deleted']);
    } catch (Exception $e) {
        jsonError($e->getMessage(), 500);
    }
}

// invalid method
jsonError('Method not allowed', 405);

