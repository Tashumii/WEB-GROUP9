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
            $params[] = "%" . like_escape($search) . "%";
            $params[] = "%" . like_escape($search) . "%";
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
            JOIN services sv ON s.item_id = sv.id AND sv.business_id = s.business_id
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
        jsonDbError($e);
    }
}

// create service
if ($method === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? 'create';

    if ($action !== 'create') {
        requireAdmin($pdo);
    }

    // update action
    if ($action === 'update') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) jsonError('Service ID required');

        $name = trim($body['name'] ?? '');
        $chargedPer = trim($body['charged_per'] ?? 'Page');
        $price = (float)($body['price'] ?? 0);
        $description = trim($body['description'] ?? '');
        $status = in_array($body['status'] ?? '', ['active', 'inactive'], true) ? $body['status'] : 'active';

        if ($name === '' || $price < 0) {
            jsonError('Valid service name and price are required');
        }
        if (strlen($name) > 255) jsonError('Service name is too long');
        if (strlen($chargedPer) > 50) jsonError('Charged per value is too long');
        $chargedPer = $chargedPer === '' ? 'Page' : $chargedPer;

        try {
            $check = $pdo->prepare("SELECT id FROM services WHERE id = ? AND business_id = ?");
            $check->execute([$id, $businessId]);
            if (!$check->fetch()) {
                jsonError('Service not found', 404);
            }

            $stmt = $pdo->prepare("
                UPDATE services 
                SET name = ?, charged_per = ?, price = ?, description = ?, status = ?
                WHERE id = ? AND business_id = ?
            ");
            $stmt->execute([$name, $chargedPer, $price, $description, $status, $id, $businessId]);
            jsonResponse(['success' => true, 'message' => 'Service updated']);
        } catch (Exception $e) {
            jsonDbError($e);
        }
    }

    // delete action
    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) jsonError('Service ID required');

        try {
            $check = $pdo->prepare("SELECT id FROM services WHERE id = ? AND business_id = ?");
            $check->execute([$id, $businessId]);
            if (!$check->fetch()) {
                jsonError('Service not found', 404);
            }

            $saleCheck = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE business_id = ? AND item_type = 'service' AND item_id = ?");
            $saleCheck->execute([$businessId, $id]);
            if ((int)$saleCheck->fetchColumn() > 0) {
                jsonError('Cannot delete a service with sales history. Set it to inactive instead.', 409);
            }

            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND business_id = ?");
            $stmt->execute([$id, $businessId]);
            jsonResponse(['success' => true, 'message' => 'Service deleted']);
        } catch (Exception $e) {
            jsonDbError($e);
        }
    }

    if ($action !== 'create') {
        jsonError('Unknown action');
    }

    // add service
    $name = trim($body['name'] ?? '');
    $chargedPer = trim($body['charged_per'] ?? 'Page');
    $price = (float)($body['price'] ?? 0);
    $description = trim($body['description'] ?? '');
    $status = in_array($body['status'] ?? '', ['active', 'inactive'], true) ? $body['status'] : 'active';

    if ($name === '' || $price < 0) {
        jsonError('Valid service name and price are required');
    }
    if (strlen($name) > 255) jsonError('Service name is too long');
    if (strlen($chargedPer) > 50) jsonError('Charged per value is too long');
    $chargedPer = $chargedPer === '' ? 'Page' : $chargedPer;

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
        jsonDbError($e);
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
    $status = in_array($body['status'] ?? '', ['active', 'inactive'], true) ? $body['status'] : 'active';

    if ($name === '' || $price < 0) {
        jsonError('Valid service name and price are required');
    }
    if (strlen($name) > 255) jsonError('Service name is too long');
    if (strlen($chargedPer) > 50) jsonError('Charged per value is too long');
    $chargedPer = $chargedPer === '' ? 'Page' : $chargedPer;

    try {
        $check = $pdo->prepare("SELECT id FROM services WHERE id = ? AND business_id = ?");
        $check->execute([$id, $businessId]);
        if (!$check->fetch()) {
            jsonError('Service not found', 404);
        }

        $stmt = $pdo->prepare("
            UPDATE services 
            SET name = ?, charged_per = ?, price = ?, description = ?, status = ?
            WHERE id = ? AND business_id = ?
        ");
        $stmt->execute([$name, $chargedPer, $price, $description, $status, $id, $businessId]);
        jsonResponse(['success' => true, 'message' => 'Service updated']);
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
    if ($id <= 0) jsonError('Service ID required');

    try {
        $check = $pdo->prepare("SELECT id FROM services WHERE id = ? AND business_id = ?");
        $check->execute([$id, $businessId]);
        if (!$check->fetch()) {
            jsonError('Service not found', 404);
        }

        $saleCheck = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE business_id = ? AND item_type = 'service' AND item_id = ?");
        $saleCheck->execute([$businessId, $id]);
        if ((int)$saleCheck->fetchColumn() > 0) {
            jsonError('Cannot delete a service with sales history. Set it to inactive instead.', 409);
        }

        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $businessId]);
        jsonResponse(['success' => true, 'message' => 'Service deleted']);
    } catch (Exception $e) {
        jsonDbError($e);
    }
}

// invalid method
jsonError('Method not allowed', 405);

