<?php
require_once __DIR__ . '/connectdb.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    $userId = getCurrentUserId();
    if ($userId === null) {
        jsonError('Authentication required.', 401);
    }

    try {
        $memberships = getUserMemberships($pdo, $userId);
        jsonResponse([
            'success' => true,
            'active_business_id' => (int)($_SESSION['business_id'] ?? 0),
            'businesses' => array_map(
                static fn(array $membership): array => [
                    'id' => (int)$membership['business_id'],
                    'name' => $membership['business_name'],
                    'role' => $membership['role'],
                ],
                $memberships
            ),
        ]);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

if ($method !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$body = getJsonBody();
$action = (string)($body['action'] ?? '');

if ($action !== 'create') {
    jsonError('Unknown action.', 400);
}

$userId = getCurrentUserId();
if ($userId === null) {
    jsonError('Authentication required.', 401);
}

$name = input_string($body, 'name', 255);
if ($name === '') {
    jsonError('Business name is required.', 400);
}

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare("INSERT INTO businesses (name) VALUES (?)");
    $insert->execute([$name]);
    $businessId = (int)$pdo->lastInsertId();

    $memberInsert = $pdo->prepare("
        INSERT INTO memberships (user_id, business_id, role, created_by)
        VALUES (?, ?, 'owner', ?)
    ");
    $memberInsert->execute([$userId, $businessId, $userId]);

    $pdo->commit();

    $membership = switch_user_business($pdo, $businessId);

    jsonResponse([
        'success' => true,
        'message' => 'Business created.',
        'business' => [
            'id' => $businessId,
            'name' => $name,
            'role' => 'owner',
        ],
        'active_business_id' => $businessId,
        'active_role' => 'owner',
        'businesses' => array_map(
            static fn(array $item): array => [
                'id' => (int)$item['business_id'],
                'name' => $item['business_name'],
                'role' => $item['role'],
            ],
            getUserMemberships($pdo, $userId)
        ),
        'csrf_token' => csrf_token(),
    ], 201);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonDbError($e);
}
