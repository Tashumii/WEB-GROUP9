<?php

namespace App\Controllers\Api;

use App\Libraries\SalesService;

class RecordSales extends ApiController
{
    public function index()
    {
        $method = $this->method();
        if ($method === 'GET') {
            return $this->dispatch(function (array $body, ?int $userId, ?int $businessId) {
                $products = $this->db->query(
                    "SELECT id, name, category, price, stock_qty FROM products WHERE business_id = ? AND status = 'active' ORDER BY name ASC",
                    [$businessId]
                )->getResultArray();
                $services = $this->db->query(
                    "SELECT id, name, charged_per, price FROM services WHERE business_id = ? AND status = 'active' ORDER BY name ASC",
                    [$businessId]
                )->getResultArray();
                return $this->json([
                    'success' => true,
                    'products' => array_map(static fn (array $p): array => [
                        'id' => (int) $p['id'], 'name' => $p['name'], 'category' => $p['category'],
                        'price' => (float) $p['price'], 'stock' => (int) $p['stock_qty'], 'type' => 'Product',
                    ], $products),
                    'services' => array_map(static fn (array $s): array => [
                        'id' => (int) $s['id'], 'name' => $s['name'], 'charged_per' => $s['charged_per'],
                        'price' => (float) $s['price'], 'type' => 'Service',
                    ], $services),
                ]);
            });
        }
        if ($method !== 'POST') {
            return $this->jsonError('Method not allowed.', 405);
        }
        return $this->dispatch(function (array $body, ?int $userId, ?int $businessId) {
            return $this->json((new SalesService($this->db))->record((int) $businessId, $userId, $body), 201);
        });
    }
}
