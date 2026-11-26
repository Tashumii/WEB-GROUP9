<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['username', 'email', 'password_hash', 'is_active', 'must_change_password', 'last_login_at', 'updated_at'];
    protected $useTimestamps = false;
}
