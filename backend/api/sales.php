<?php
// load db
require_once __DIR__ . '/connectdb.php';

// request method
$method = $_SERVER['REQUEST_METHOD'];
$businessId = getActiveBusinessId($pdo);

// fetch sales
if ($method === 'GET') {
    try {
        $range = trim($_GET['range'] ?? 'all');
        $dateFrom = trim($_GET['date_from'] ?? $_GET['from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? $_GET['to'] ?? '');
        $type = strtolower(trim($_GET['type'] ?? 'all'));
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        // date conditions
        $where = ["s.business_id = ?"];
        $params = [$businessId];

        // range filter
        if ($range === 'today') {
            $where[] = "DATE(s.sold_at) = CURDATE()";
        } elseif ($range === 'week') {
            $where[] = "s.sold_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($range === 'month') {
            $where[] = "MONTH(s.sold_at) = MONTH(CURDATE()) AND YEAR(s.sold_at) = YEAR(CURDATE())";
        } elseif ($dateFrom !== '' && $dateTo !== '') {
            $where[] = "DATE(s.sold_at) BETWEEN ? AND ?";
            $params[] = $dateFrom;
            $params[] = $dateTo;
        } elseif ($dateFrom !== '') {
            $where[] = "DATE(s.sold_at) >= ?";
            $params[] = $dateFrom;
        } elseif ($dateTo !== '') {
            $where[] = "DATE(s.sold_at) <= ?";
            $params[] = $dateTo;
        }

        // type filter
        if ($type === 'product' || $type === 'service') {
            $where[] = "s.item_type = ?";
            $params[] = $type;
        }

        $whereClause = implode(" AND ", $where);

        // query sales
        $sql = "
            SELECT 
                s.id,
                s.business_id,
                s.item_type,
                s.item_id,
                s.quantity,
                s.unit_price,
                s.total,
                s.sold_at,
                CASE 
                    WHEN s.item_type = 'product' THEN COALESCE(p.name, 'Unknown Product')
                    WHEN s.item_type = 'service' THEN COALESCE(sv.name, 'Unknown Service')
                    ELSE 'Unknown'
                END AS item_name
            FROM sales s
            LEFT JOIN products p ON s.item_type = 'product' AND s.item_id = p.id
            LEFT JOIN services sv ON s.item_type = 'service' AND s.item_id = sv.id
            WHERE {$whereClause}
        ";

        // search filter
        if ($search !== '') {
            $sql .= " AND (
                (s.item_type = 'product' AND p.name LIKE ?) OR 
                (s.item_type = 'service' AND sv.name LIKE ?)
            )";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY s.sold_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // format rows
        $formattedSales = [];
        $totalSales = 0.0;
        $productTotal = 0.0;
        $serviceTotal = 0.0;

        foreach ($rows as $row) {
            $amount = (float)$row['total'];
            $totalSales += $amount;

            if ($row['item_type'] === 'product') {
                $productTotal += $amount;
            } elseif ($row['item_type'] === 'service') {
                $serviceTotal += $amount;
            }

            $timestamp = strtotime($row['sold_at']);
            $formattedSales[] = [
                'id' => (int)$row['id'],
                'date' => date('M j', $timestamp),
                'raw_date' => date('Y-m-d', $timestamp),
                'time' => date('g:iA', $timestamp),
                'item_name' => $row['item_name'],
                'type' => ucfirst($row['item_type']),
                'quantity' => (int)$row['quantity'],
                'unit_price' => (float)$row['unit_price'],
                'total' => $amount
            ];
        }

        // calc percentages
        $productPct = $totalSales > 0 ? round(($productTotal / $totalSales) * 100) : 0;
        $servicePct = $totalSales > 0 ? (100 - $productPct) : 0;

        jsonResponse([
            'success' => true,
            'summary' => [
                'total_amount' => $totalSales,
                'record_count' => count($formattedSales),
                'product_revenue' => $productTotal,
                'service_revenue' => $serviceTotal,
                'product_percentage' => (int)$productPct,
                'service_percentage' => (int)$servicePct,
                'percentage_label' => "{$productPct}% / {$servicePct}%"
            ],
            'sales' => $formattedSales
        ]);
    } catch (Exception $e) {
        jsonError($e->getMessage(), 500);
    }
}

// post redirect
if ($method === 'POST') {
    require __DIR__ . '/recordsales.php';
    exit;
}

// invalid method
jsonError('Method not allowed', 405);

