<?php

namespace App\Models;

use CodeIgniter\Model;

class SaleModel extends Model
{
    protected $table = 'sales';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['business_id', 'recorded_by', 'item_type', 'item_id', 'quantity', 'unit_price', 'total', 'sold_at'];
    protected $useTimestamps = false;
}
