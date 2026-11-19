<?php

namespace App\Models;

use CodeIgniter\Model;

class RateLimitModel extends Model
{
    protected $table = 'rate_limits';
    protected $primaryKey = 'limit_key';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = ['limit_key', 'window_start', 'hits', 'updated_at'];
    protected $useTimestamps = false;
}
