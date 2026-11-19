<?php

namespace App\Controllers\Api;

use App\Exceptions\ApiException;
use DateTime;

class Analytics extends ApiController
{
    public function index()
    {
        if ($this->method() !== 'GET') {
            return $this->jsonError('Method not allowed', 405);
        }

        return $this->dispatch(function (array $body, ?int $userId, ?int $businessId) {
            $dateFrom = trim((string) ($this->request->getGet('date_from') ?? $this->request->getGet('from') ?? ''));
            $dateTo = trim((string) ($this->request->getGet('date_to') ?? $this->request->getGet('to') ?? ''));
            $compare = $this->request->getGet('compare') === 'true';

            if ($dateFrom !== '' && ! $this->isValidDate($dateFrom)) {
                throw new ApiException('Invalid date_from format', 400);
            }
            if ($dateTo !== '' && ! $this->isValidDate($dateTo)) {
                throw new ApiException('Invalid date_to format', 400);
            }
            if ($dateFrom === '' || $dateTo === '') {
                $dateFrom = date('Y-m-01');
                $dateTo = date('Y-m-t');
            }
            if ($dateFrom > $dateTo) {
                throw new ApiException('date_from cannot be after date_to', 400);
            }
            if ((new DateTime($dateFrom))->diff(new DateTime($dateTo))->days > 366) {
                throw new ApiException('Date range cannot exceed 366 days', 400);
            }

            $revStats = $this->db->query(
                "SELECT COALESCE(SUM(total), 0) AS total_revenue,
                 COALESCE(SUM(CASE WHEN item_type = 'product' THEN total ELSE 0 END), 0) AS product_revenue,
                 COALESCE(SUM(CASE WHEN item_type = 'service' THEN total ELSE 0 END), 0) AS service_revenue,
                 COUNT(*) AS total_transactions FROM sales
                 WHERE business_id = ? AND DATE(sold_at) BETWEEN ? AND ?",
                [$businessId, $dateFrom, $dateTo]
            )->getRowArray();
            $totalRevenue = (float) $revStats['total_revenue'];
            $productRevenue = (float) $revStats['product_revenue'];
            $serviceRevenue = (float) $revStats['service_revenue'];
            $productPercentage = $totalRevenue > 0 ? (int) round(($productRevenue / $totalRevenue) * 100) : 0;
            $servicePercentage = $totalRevenue > 0 ? 100 - $productPercentage : 0;

            $bestProduct = $this->db->query(
                "SELECT p.name, SUM(s.total) AS revenue, SUM(s.quantity) AS units FROM sales s
                 JOIN products p ON s.item_id = p.id AND p.business_id = s.business_id
                 WHERE s.business_id = ? AND s.item_type = 'product' AND DATE(s.sold_at) BETWEEN ? AND ?
                 GROUP BY p.id, p.name ORDER BY revenue DESC LIMIT 1",
                [$businessId, $dateFrom, $dateTo]
            )->getRowArray();
            $bestService = $this->db->query(
                "SELECT sv.name, SUM(s.total) AS revenue, SUM(s.quantity) AS units FROM sales s
                 JOIN services sv ON s.item_id = sv.id AND sv.business_id = s.business_id
                 WHERE s.business_id = ? AND s.item_type = 'service' AND DATE(s.sold_at) BETWEEN ? AND ?
                 GROUP BY sv.id, sv.name ORDER BY revenue DESC LIMIT 1",
                [$businessId, $dateFrom, $dateTo]
            )->getRowArray();

            $trendRows = $this->db->query(
                'SELECT DATE(sold_at) AS sale_date, SUM(total) AS daily_total FROM sales
                 WHERE business_id = ? AND DATE(sold_at) BETWEEN ? AND ? GROUP BY DATE(sold_at) ORDER BY sale_date ASC',
                [$businessId, $dateFrom, $dateTo]
            )->getResultArray();
            $trendMap = [];
            foreach ($trendRows as $row) {
                $trendMap[$row['sale_date']] = (float) $row['daily_total'];
            }
            $labels = [];
            $revenueSeries = [];
            $start = strtotime($dateFrom);
            $end = strtotime($dateTo);
            for ($timestamp = $start; $timestamp <= $end; $timestamp = strtotime('+1 day', $timestamp)) {
                $date = date('Y-m-d', $timestamp);
                $labels[] = date('M j', $timestamp);
                $revenueSeries[] = $trendMap[$date] ?? 0;
            }

            $topItems = $this->db->query(
                "SELECT s.item_type, s.item_id,
                 CASE WHEN s.item_type = 'product' THEN COALESCE(p.name, 'Product')
                      WHEN s.item_type = 'service' THEN COALESCE(sv.name, 'Service') END AS item_name,
                 SUM(s.quantity) AS total_units, SUM(s.total) AS total_revenue
                 FROM sales s LEFT JOIN products p ON s.item_type = 'product' AND s.item_id = p.id AND p.business_id = s.business_id
                 LEFT JOIN services sv ON s.item_type = 'service' AND s.item_id = sv.id AND sv.business_id = s.business_id
                 WHERE s.business_id = ? AND DATE(s.sold_at) BETWEEN ? AND ?
                 GROUP BY s.item_type, s.item_id, item_name ORDER BY total_revenue DESC LIMIT 5",
                [$businessId, $dateFrom, $dateTo]
            )->getResultArray();
            $rankedTop = [];
            foreach ($topItems as $index => $item) {
                $rankedTop[] = [
                    'rank' => $index + 1,
                    'name' => $item['item_name'],
                    'type' => ucfirst($item['item_type']),
                    'units' => (int) $item['total_units'],
                    'revenue' => (float) $item['total_revenue'],
                ];
            }

            $comparison = [];
            if ($compare) {
                $allowedIds = array_column($this->auth->memberships((int) $userId), 'business_id');
                if ($allowedIds === []) {
                    throw new ApiException('No business assigned to this account', 403);
                }
                $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
                $comparisonRows = $this->db->query(
                    "SELECT b.id AS business_id, b.name AS business_name,
                     COALESCE(SUM(s.total), 0) AS total_revenue, COUNT(s.id) AS sales_count,
                     COALESCE(SUM(CASE WHEN s.item_type = 'product' THEN s.total ELSE 0 END), 0) AS product_revenue,
                     COALESCE(SUM(CASE WHEN s.item_type = 'service' THEN s.total ELSE 0 END), 0) AS service_revenue
                     FROM businesses b LEFT JOIN sales s ON s.business_id = b.id AND DATE(s.sold_at) BETWEEN ? AND ?
                     WHERE b.id IN ({$placeholders}) GROUP BY b.id, b.name ORDER BY b.id ASC",
                    array_merge([$dateFrom, $dateTo], $allowedIds)
                )->getResultArray();
                foreach ($comparisonRows as $row) {
                    $comparison[] = [
                        'business_id' => (int) $row['business_id'],
                        'name' => $row['business_name'],
                        'total_revenue' => (float) $row['total_revenue'],
                        'sales_count' => (int) $row['sales_count'],
                        'product_revenue' => (float) $row['product_revenue'],
                        'service_revenue' => (float) $row['service_revenue'],
                    ];
                }
            }

            return $this->json([
                'success' => true,
                'period' => ['from' => $dateFrom, 'to' => $dateTo],
                'kpi' => [
                    'total_revenue' => $totalRevenue,
                    'total_transactions' => (int) $revStats['total_transactions'],
                    'product_revenue' => $productRevenue,
                    'service_revenue' => $serviceRevenue,
                    'product_percentage' => $productPercentage,
                    'service_percentage' => $servicePercentage,
                    'best_product' => $bestProduct ? [
                        'name' => $bestProduct['name'], 'revenue' => (float) $bestProduct['revenue'], 'units' => (int) $bestProduct['units'],
                    ] : null,
                    'best_service' => $bestService ? [
                        'name' => $bestService['name'], 'revenue' => (float) $bestService['revenue'], 'units' => (int) $bestService['units'],
                    ] : null,
                ],
                'chart' => [
                    'labels' => $labels,
                    'revenue_series' => $revenueSeries,
                    'products_percentage' => $productPercentage,
                    'services_percentage' => $servicePercentage,
                ],
                'top_items' => $rankedTop,
                'comparison' => $comparison,
            ]);
        });
    }
}
