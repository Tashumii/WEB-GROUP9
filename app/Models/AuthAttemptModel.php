<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthAttemptModel extends Model
{
    protected $table = 'auth_attempts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['action', 'username', 'ip_address', 'success'];
    protected $useTimestamps = false;
}
