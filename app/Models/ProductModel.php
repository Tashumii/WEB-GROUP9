<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['business_id', 'name', 'category', 'price', 'stock_qty', 'status'];
    protected $useTimestamps = false;
}
