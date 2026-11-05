<?php
// load db
require_once __DIR__ . '/connectdb.php';

// request method
$method = $_SERVER['REQUEST_METHOD'];
$businessId = getActiveBusinessId($pdo);

if ($method !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $dateFrom = trim($_GET['date_from'] ?? $_GET['from'] ?? '');
    $dateTo = trim($_GET['date_to'] ?? $_GET['to'] ?? '');
    $compare = isset($_GET['compare']) && $_GET['compare'] === 'true';

    // default dates
    if ($dateFrom === '' || $dateTo === '') {
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-t');
    }

    // total revenue
    $revStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(total), 0) AS total_revenue,
            COALESCE(SUM(CASE WHEN item_type = 'product' THEN total ELSE 0 END), 0) AS product_revenue,
            COALESCE(SUM(CASE WHEN item_type = 'service' THEN total ELSE 0 END), 0) AS service_revenue,
            COUNT(*) AS total_transactions
        FROM sales 
        WHERE business_id = ? AND DATE(sold_at) BETWEEN ? AND ?
    ");
    $revStmt->execute([$businessId, $dateFrom, $dateTo]);
    $revStats = $revStmt->fetch();

    $totalRev = (float)$revStats['total_revenue'];
    $prodRev = (float)$revStats['product_revenue'];
    $servRev = (float)$revStats['service_revenue'];

    $prodPct = $totalRev > 0 ? round(($prodRev / $totalRev) * 100) : 62;
    $servPct = $totalRev > 0 ? (100 - $prodPct) : 38;

    // best product
    $bpStmt = $pdo->prepare("
        SELECT p.name, SUM(s.total) AS revenue, SUM(s.quantity) AS units
        FROM sales s
        JOIN products p ON s.item_id = p.id
        WHERE s.business_id = ? AND s.item_type = 'product' AND DATE(s.sold_at) BETWEEN ? AND ?
        GROUP BY p.id, p.name
        ORDER BY revenue DESC
        LIMIT 1
    ");
    $bpStmt->execute([$businessId, $dateFrom, $dateTo]);
    $bestProduct = $bpStmt->fetch();

    // best service
    $bsStmt = $pdo->prepare("
        SELECT sv.name, SUM(s.total) AS revenue, SUM(s.quantity) AS units
        FROM sales s
        JOIN services sv ON s.item_id = sv.id
        WHERE s.business_id = ? AND s.item_type = 'service' AND DATE(s.sold_at) BETWEEN ? AND ?
        GROUP BY sv.id, sv.name
        ORDER BY revenue DESC
        LIMIT 1
    ");
    $bsStmt->execute([$businessId, $dateFrom, $dateTo]);
    $bestService = $bsStmt->fetch();

    // daily trend
    $trendStmt = $pdo->prepare("
        SELECT DATE(sold_at) AS sale_date, SUM(total) AS daily_total
        FROM sales
        WHERE business_id = ? AND DATE(sold_at) BETWEEN ? AND ?
        GROUP BY DATE(sold_at)
        ORDER BY sale_date ASC
    ");
    $trendStmt->execute([$businessId, $dateFrom, $dateTo]);
    $trendRows = $trendStmt->fetchAll();

    $trendMap = [];
    foreach ($trendRows as $tr) {
        $trendMap[$tr['sale_date']] = (float)$tr['daily_total'];
    }

    // fill days
    $labels = [];
    $dataSeries = [];
    $startTs = strtotime($dateFrom);
    $endTs = strtotime($dateTo);

    for ($ts = $startTs; $ts <= $endTs; $ts = strtotime('+1 day', $ts)) {
        $dStr = date('Y-m-d', $ts);
        $labels[] = date('M j', $ts);
        $dataSeries[] = $trendMap[$dStr] ?? 0;
    }

    // top items
    $topStmt = $pdo->prepare("
        SELECT 
            s.item_type,
            s.item_id,
            CASE 
                WHEN s.item_type = 'product' THEN COALESCE(p.name, 'Product')
                WHEN s.item_type = 'service' THEN COALESCE(sv.name, 'Service')
            END AS item_name,
            SUM(s.quantity) AS total_units,
            SUM(s.total) AS total_revenue
        FROM sales s
        LEFT JOIN products p ON s.item_type = 'product' AND s.item_id = p.id
        LEFT JOIN services sv ON s.item_type = 'service' AND s.item_id = sv.id
        WHERE s.business_id = ? AND DATE(s.sold_at) BETWEEN ? AND ?
        GROUP BY s.item_type, s.item_id, item_name
        ORDER BY total_revenue DESC
        LIMIT 5
    ");
    $topStmt->execute([$businessId, $dateFrom, $dateTo]);
    $topItems = $topStmt->fetchAll();

    $rankedTop = [];
    $rank = 1;
    foreach ($topItems as $item) {
        $rankedTop[] = [
            'rank' => $rank++,
            'name' => $item['item_name'],
            'type' => ucfirst($item['item_type']),
            'units' => (int)$item['total_units'],
            'revenue' => (float)$item['total_revenue']
        ];
    }

    // business comparison
    $comparison = [];
    if ($compare) {
        $compStmt = $pdo->prepare("
            SELECT 
                b.id AS business_id,
                b.name AS business_name,
                COALESCE(SUM(s.total), 0) AS total_revenue,
                COUNT(s.id) AS sales_count,
                COALESCE(SUM(CASE WHEN s.item_type = 'product' THEN s.total ELSE 0 END), 0) AS product_revenue,
                COALESCE(SUM(CASE WHEN s.item_type = 'service' THEN s.total ELSE 0 END), 0) AS service_revenue
            FROM businesses b
            LEFT JOIN sales s ON s.business_id = b.id AND DATE(s.sold_at) BETWEEN ? AND ?
            GROUP BY b.id, b.name
            ORDER BY b.id ASC
        ");
        $compStmt->execute([$dateFrom, $dateTo]);
        $compRows = $compStmt->fetchAll();
        foreach ($compRows as $cr) {
            $comparison[] = [
                'business_id' => (int)$cr['business_id'],
                'name' => $cr['business_name'],
                'total_revenue' => (float)$cr['total_revenue'],
                'sales_count' => (int)$cr['sales_count'],
                'product_revenue' => (float)$cr['product_revenue'],
                'service_revenue' => (float)$cr['service_revenue']
            ];
        }
    }

    jsonResponse([
        'success' => true,
        'period' => [
            'from' => $dateFrom,
            'to' => $dateTo
        ],
        'kpi' => [
            'total_revenue' => $totalRev,
            'product_revenue' => $prodRev,
            'service_revenue' => $servRev,
            'product_percentage' => (int)$prodPct,
            'service_percentage' => (int)$servPct,
            'best_product' => $bestProduct ? [
                'name' => $bestProduct['name'],
                'revenue' => (float)$bestProduct['revenue'],
                'units' => (int)$bestProduct['units']
            ] : null,
            'best_service' => $bestService ? [
                'name' => $bestService['name'],
                'revenue' => (float)$bestService['revenue'],
                'units' => (int)$bestService['units']
            ] : null
        ],
        'chart' => [
            'labels' => $labels,
            'revenue_series' => $dataSeries,
            'products_percentage' => (int)$prodPct,
            'services_percentage' => (int)$servPct
        ],
        'top_items' => $rankedTop,
        'comparison' => $comparison
    ]);
} catch (Exception $e) {
    jsonError($e->getMessage(), 500);
}
