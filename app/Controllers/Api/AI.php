<?php

namespace App\Controllers\Api;

use App\Exceptions\ApiException;

class AI extends ApiController
{
    public function index()
    {
        $method = $this->method();
        if ($method !== 'POST') {
            return $this->jsonError('Method not allowed.', 405);
        }

        return $this->dispatch(function (array $body, ?int $userId, ?int $businessId) {
            $action = (string) ($body['action'] ?? 'pricing_advice');
            if ($action !== 'pricing_advice') {
                throw new ApiException('Unknown action.', 400);
            }

            $itemType = (string) ($body['item_type'] ?? '');
            $itemId = (int) ($body['item_id'] ?? 0);
            if (! in_array($itemType, ['product', 'service'], true) || $itemId <= 0) {
                throw new ApiException('item_type and item_id are required', 422);
            }

            $table = $itemType === 'product' ? 'products' : 'services';
            $row = $this->db->table($table)->where(['id' => $itemId, 'business_id' => $businessId])->get()->getRowArray();
            if (! $row) {
                throw new ApiException('Item not found', 404);
            }

            // 90-day history for this item
            $days = $this->db->query(
                "SELECT DATE(sold_at) AS d, SUM(quantity) AS units, SUM(total) AS revenue, COUNT(*) AS txns
                 FROM sales WHERE business_id = ? AND item_type = ? AND item_id = ? AND sold_at >= (NOW() - INTERVAL 90 DAY)
                 GROUP BY DATE(sold_at) ORDER BY d ASC",
                [$businessId, $itemType, $itemId]
            )->getResultArray();

            $agg = $this->db->query(
                "SELECT COALESCE(SUM(quantity),0) AS units, COALESCE(SUM(total),0) AS revenue, COUNT(*) AS txns,
                        COALESCE(MIN(unit_price),0) AS min_price, COALESCE(MAX(unit_price),0) AS max_price
                 FROM sales WHERE business_id = ? AND item_type = ? AND item_id = ? AND sold_at >= (NOW() - INTERVAL 90 DAY)",
                [$businessId, $itemType, $itemId]
            )->getRowArray();

            $biz = $this->db->query(
                "SELECT COALESCE(SUM(total),0) AS total_revenue, COUNT(*) AS txns FROM sales WHERE business_id = ? AND sold_at >= (NOW() - INTERVAL 90 DAY)",
                [$businessId]
            )->getRowArray();

            $lines = [];
            $lines[] = 'You are a pricing advisor for a small school-supplies / printing shop (POS).';
            $lines[] = 'Give concise, actionable pricing advice for ONE item. Use the sales data below.';
            $lines[] = 'Respond in 4 short sections: (1) Summary, (2) Observation, (3) Recommendation, (4) Risk/Caveat.';
            $lines[] = 'Include a suggested price (with currency symbol PHP) and a % change if you recommend changing it.';
            $lines[] = '';
            $lines[] = 'Item type: ' . $itemType;
            $lines[] = 'Item name: ' . $row['name'];
            if ($itemType === 'product') {
                $lines[] = 'Category: ' . ($row['category'] ?? 'General');
                $lines[] = 'Current price: PHP ' . number_format((float) $row['price'], 2);
                $lines[] = 'Current stock: ' . (int) ($row['stock_qty'] ?? 0);
            } else {
                $lines[] = 'Charged per: ' . ($row['charged_per'] ?? 'Page');
                $lines[] = 'Current price: PHP ' . number_format((float) $row['price'], 2);
                $lines[] = 'Description: ' . ($row['description'] ?? '');
            }
            $lines[] = 'Status: ' . $row['status'];
            $lines[] = '';
            $lines[] = 'Last 90 days for this item:';
            $lines[] = '- Units sold: ' . (int) ($agg['units'] ?? 0);
            $lines[] = '- Revenue: PHP ' . number_format((float) ($agg['revenue'] ?? 0), 2);
            $lines[] = '- Transactions: ' . (int) ($agg['txns'] ?? 0);
            $lines[] = '- Price range charged: PHP ' . number_format((float) ($agg['min_price'] ?? 0), 2) . ' - PHP ' . number_format((float) ($agg['max_price'] ?? 0), 2);
            $lines[] = '';
            $lines[] = 'Shop-wide last 90 days: revenue PHP ' . number_format((float) ($biz['total_revenue'] ?? 0), 2) . ', transactions ' . (int) ($biz['txns'] ?? 0);
            $lines[] = '';
            if (! empty($days)) {
                $lines[] = 'Daily sales (date: units, revenue):';
                foreach ($days as $d) {
                    $lines[] = '  ' . $d['d'] . ': ' . (int) $d['units'] . ' units, PHP ' . number_format((float) $d['revenue'], 2);
                }
            } else {
                $lines[] = 'No sales recorded for this item in the last 90 days.';
            }
            $lines[] = '';
            $lines[] = 'Consider demand, margin, competitor context (unknown), and stock levels. If no sales, suggest a trial price or promotion.';
            $prompt = implode("\n", $lines);

            $url = rtrim((string) env('OPENCODE_URL', 'http://127.0.0.1:4096'), '/');
            $password = (string) env('OPENCODE_PASSWORD', '');
            $model = (string) env('OPENCODE_MODEL', '');

            $authHeader = $password !== '' ? 'Authorization: Basic ' . base64_encode('opencode:' . $password) : '';

            $call = function (string $method, string $endpoint, ?string $payload = null) use ($url, $authHeader): array {
                $ch = curl_init($url . $endpoint);
                $headers = ['Content-Type: application/json', 'Accept: application/json'];
                if ($authHeader !== '') {
                    $headers[] = $authHeader;
                }
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                if ($payload !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                }
                $resp = curl_exec($ch);
                $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err = (string) curl_error($ch);
                curl_close($ch);
                return ['code' => $code, 'body' => $resp, 'error' => $err];
            };

            $create = $call('POST', '/session', json_encode(['title' => 'pricing-advice']));
            if ($create['code'] < 200 || $create['code'] >= 300) {
                throw new ApiException('OpenCode server unreachable: ' . ($create['error'] ?: 'HTTP ' . $create['code']), 502);
            }
            $session = json_decode((string) $create['body'], true);
            $sid = $session['id'] ?? null;
            if (! $sid) {
                throw new ApiException('Failed to create OpenCode session', 502);
            }

            $msgBody = ['parts' => [['type' => 'text', 'text' => $prompt]]];
            if ($model !== '') {
                $msgBody['model'] = $model;
            }
            $send = $call('POST', '/session/' . rawurlencode($sid) . '/message', json_encode($msgBody));
            if ($send['code'] < 200 || $send['code'] >= 300) {
                $call('DELETE', '/session/' . rawurlencode($sid));
                throw new ApiException('OpenCode request failed: ' . ($send['error'] ?: 'HTTP ' . $send['code']), 502);
            }
            $msg = json_decode((string) $send['body'], true);

            $advice = '';
            if (is_array($msg)) {
                foreach (($msg['parts'] ?? []) as $part) {
                    if (($part['type'] ?? '') === 'text' && isset($part['text'])) {
                        $advice .= $part['text'];
                    }
                }
            }

            $call('DELETE', '/session/' . rawurlencode($sid));

            if (trim($advice) === '') {
                throw new ApiException('No advice returned from OpenCode', 502);
            }

            return $this->json([
                'success' => true,
                'item_type' => $itemType,
                'item_id' => $itemId,
                'name' => $row['name'],
                'current_price' => (float) $row['price'],
                'advice' => trim($advice),
                'model' => $model !== '' ? $model : 'default',
            ]);
        });
    }
}
