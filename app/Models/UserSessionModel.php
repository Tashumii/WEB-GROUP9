<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSessionModel extends Model
{
    protected $table = 'user_sessions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['session_hash', 'user_id', 'ip_address', 'user_agent', 'expires_at', 'last_seen_at', 'revoked_at'];
    protected $useTimestamps = false;
}
