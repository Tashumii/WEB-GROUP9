<?php
// load db
require_once __DIR__ . '/connectdb.php';

// request method
$method = $_SERVER['REQUEST_METHOD'];
$businessId = getActiveBusinessId($pdo);

// fetch catalog
if ($method === 'GET') {
    try {
        // fetch products
        $pStmt = $pdo->prepare("
            SELECT id, name, category, price, stock_qty, 'Product' AS type
            FROM products
            WHERE business_id = ? AND status = 'active'
            ORDER BY name ASC
        ");
        $pStmt->execute([$businessId]);
        $products = $pStmt->fetchAll();

        // fetch services
        $sStmt = $pdo->prepare("
            SELECT id, name, charged_per, price, 'Service' AS type
            FROM services
            WHERE business_id = ? AND status = 'active'
            ORDER BY name ASC
        ");
        $sStmt->execute([$businessId]);
        $services = $sStmt->fetchAll();

        jsonResponse([
            'success' => true,
            'products' => array_map(function ($p) {
                return [
                    'id' => (int)$p['id'],
                    'name' => $p['name'],
                    'category' => $p['category'],
                    'price' => (float)$p['price'],
                    'stock' => (int)$p['stock_qty'],
                    'type' => 'Product'
                ];
            }, $products),
            'services' => array_map(function ($s) {
                return [
                    'id' => (int)$s['id'],
                    'name' => $s['name'],
                    'charged_per' => $s['charged_per'],
                    'price' => (float)$s['price'],
                    'type' => 'Service'
                ];
            }, $services)
        ]);
    } catch (Exception $e) {
        jsonDbError($e);
    }
}

// record sale
if ($method === 'POST') {
    $body = getJsonBody();
    $recordedBy = getCurrentUserId();

    // normalize items
    $items = [];
    if (isset($body['items']) && is_array($body['items'])) {
        $items = $body['items'];
    } elseif (isset($body['item_id'])) {
        $items[] = $body;
    }

    if (empty($items)) {
        jsonError('No sale items provided');
    }

    if (count($items) > 100) {
        jsonError('Too many items in a single sale');
    }

    // begin transaction
    $pdo->beginTransaction();
    $insertedSales = [];
    $totalSaleAmount = 0;

    try {
        // prepare statements
        $checkProduct = $pdo->prepare("SELECT id, name, price, stock_qty, status FROM products WHERE id = ? AND business_id = ?");
        $checkService = $pdo->prepare("SELECT id, name, price, status FROM services WHERE id = ? AND business_id = ?");
        $deductStock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ? AND business_id = ? AND stock_qty >= ?");
        $insertSale = $pdo->prepare("
            INSERT INTO sales (business_id, recorded_by, item_type, item_id, quantity, unit_price, total, sold_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($items as $item) {
            $type = strtolower(trim($item['item_type'] ?? $item['type'] ?? ''));
            $itemId = (int)($item['item_id'] ?? $item['id'] ?? 0);
            $qty = (int)($item['quantity'] ?? $item['qty'] ?? 1);

            if (!in_array($type, ['product', 'service'], true)) {
                throw new Exception("Invalid item type for item {$itemId}");
            }

            if ($itemId <= 0) {
                throw new Exception('Invalid item ID');
            }

            if ($qty < 1 || $qty > 100000) {
                throw new Exception('Quantity must be between 1 and 100000');
            }

            $unitPrice = 0.0;
            $itemName = '';

            // validate product
            if ($type === 'product') {
                $checkProduct->execute([$itemId, $businessId]);
                $prod = $checkProduct->fetch();
                if (!$prod) {
                    throw new Exception("Product ID {$itemId} not found");
                }
                if ($prod['status'] !== 'active') {
                    throw new Exception("Product {$prod['name']} is inactive");
                }
                if ($prod['stock_qty'] < $qty) {
                    throw new Exception("Insufficient stock for {$prod['name']} (Available: {$prod['stock_qty']})");
                }

                $unitPrice = (float)$prod['price'];
                $itemName = $prod['name'];

                // update stock atomically
                $deductStock->execute([$qty, $itemId, $businessId, $qty]);
                if ($deductStock->rowCount() < 1) {
                    throw new Exception("Insufficient stock for {$prod['name']}");
                }
            }

            // validate service
            if ($type === 'service') {
                $checkService->execute([$itemId, $businessId]);
                $serv = $checkService->fetch();
                if (!$serv) {
                    throw new Exception("Service ID {$itemId} not found");
                }
                if ($serv['status'] !== 'active') {
                    throw new Exception("Service {$serv['name']} is inactive");
                }

                $unitPrice = (float)$serv['price'];
                $itemName = $serv['name'];
            }

            // insert record
            $lineTotal = round($qty * $unitPrice, 2);
            $insertSale->execute([$businessId, $recordedBy, $type, $itemId, $qty, $unitPrice, $lineTotal]);
            $saleId = (int)$pdo->lastInsertId();

            $totalSaleAmount += $lineTotal;
            $insertedSales[] = [
                'sale_id' => $saleId,
                'name' => $itemName,
                'type' => ucfirst($type),
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total' => $lineTotal
            ];
        }

        // commit transaction
        $pdo->commit();

        jsonResponse([
            'success' => true,
            'message' => 'Sale recorded successfully',
            'total_amount' => $totalSaleAmount,
            'items_count' => count($insertedSales),
            'sales' => $insertedSales
        ], 201);
    } catch (Exception $e) {
        // rollback transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e instanceof PDOException) {
            jsonDbError($e);
        }

        jsonError($e->getMessage(), 400);
    }
}

// invalid method
jsonError('Method not allowed', 405);

