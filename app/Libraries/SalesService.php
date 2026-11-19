<?php

namespace App\Libraries;

use App\Exceptions\ApiException;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Throwable;

class SalesService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function record(int $businessId, ?int $recordedBy, array $body): array
    {
        $items = isset($body['items']) && is_array($body['items']) ? $body['items'] : (isset($body['item_id']) ? [$body] : []);
        if ($items === []) {
            throw new ApiException('No sale items provided', 400);
        }
        if (count($items) > 100) {
            throw new ApiException('Too many items in a single sale', 400);
        }

        $insertedSales = [];
        $totalSaleAmount = 0.0;
        $this->db->transBegin();
        try {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    throw new ApiException('Invalid sale item', 400);
                }
                $type = strtolower(trim((string) ($item['item_type'] ?? $item['type'] ?? '')));
                $itemId = (int) ($item['item_id'] ?? $item['id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
                if (! in_array($type, ['product', 'service'], true)) {
                    throw new ApiException("Invalid item type for item {$itemId}", 400);
                }
                if ($itemId <= 0) {
                    throw new ApiException('Invalid item ID', 400);
                }
                if ($quantity < 1 || $quantity > 100000) {
                    throw new ApiException('Quantity must be between 1 and 100000', 400);
                }

                $table = $type === 'product' ? 'products' : 'services';
                $stockField = $type === 'product' ? ', stock_qty' : '';
                $itemRow = $this->db->query(
                    "SELECT id, name, price, status{$stockField} FROM {$table} WHERE id = ? AND business_id = ? FOR UPDATE",
                    [$itemId, $businessId]
                )->getRowArray();
                if (! $itemRow) {
                    throw new ApiException(ucfirst($type) . " ID {$itemId} not found", 400);
                }
                if ($itemRow['status'] !== 'active') {
                    throw new ApiException(ucfirst($type) . " {$itemRow['name']} is inactive", 400);
                }
                if ($type === 'product' && (int) $itemRow['stock_qty'] < $quantity) {
                    throw new ApiException("Insufficient stock for {$itemRow['name']} (Available: {$itemRow['stock_qty']})", 400);
                }

                $unitPrice = (float) $itemRow['price'];
                $lineTotal = round($quantity * $unitPrice, 2);
                if ($type === 'product') {
                    $this->db->query(
                        'UPDATE products SET stock_qty = stock_qty - ? WHERE id = ? AND business_id = ? AND stock_qty >= ?',
                        [$quantity, $itemId, $businessId, $quantity]
                    );
                    if ($this->db->affectedRows() < 1) {
                        throw new ApiException("Insufficient stock for {$itemRow['name']}", 400);
                    }
                }

                $this->db->table('sales')->insert([
                    'business_id' => $businessId,
                    'recorded_by' => $recordedBy,
                    'item_type' => $type,
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                    'sold_at' => date('Y-m-d H:i:s'),
                ]);
                $saleId = (int) $this->db->insertID();
                $totalSaleAmount += $lineTotal;
                $insertedSales[] = [
                    'sale_id' => $saleId,
                    'name' => $itemRow['name'],
                    'type' => ucfirst($type),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                ];
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Sale transaction failed.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        return [
            'success' => true,
            'message' => 'Sale recorded successfully',
            'total_amount' => $totalSaleAmount,
            'items_count' => count($insertedSales),
            'sales' => $insertedSales,
        ];
    }
}
