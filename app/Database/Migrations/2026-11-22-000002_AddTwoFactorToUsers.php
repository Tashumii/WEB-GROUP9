<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTwoFactorToUsers extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('two_factor_secret', 'users')) {
            $this->forge->addColumn('users', [
                'two_factor_secret' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'email',
                ],
            ]);
        }
        if (! $this->db->fieldExists('two_factor_enabled', 'users')) {
            $this->forge->addColumn('users', [
                'two_factor_enabled' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                    'after' => 'two_factor_secret',
                ],
            ]);
        }
        if (! $this->db->fieldExists('two_factor_recovery_codes', 'users')) {
            $this->forge->addColumn('users', [
                'two_factor_recovery_codes' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'two_factor_enabled',
                ],
            ]);
        }
        if (! $this->db->fieldExists('two_factor_last_used', 'users')) {
            $this->forge->addColumn('users', [
                'two_factor_last_used' => [
                    'type' => 'BIGINT',
                    'null' => true,
                    'after' => 'two_factor_recovery_codes',
                ],
            ]);
        }
        if (! $this->db->fieldExists('two_factor_confirmed_at', 'users')) {
            $this->forge->addColumn('users', [
                'two_factor_confirmed_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'after' => 'two_factor_last_used',
                ],
            ]);
        }
    }

    public function down()
    {
        foreach (['two_factor_confirmed_at', 'two_factor_last_used', 'two_factor_recovery_codes', 'two_factor_enabled', 'two_factor_secret'] as $column) {
            if ($this->db->fieldExists($column, 'users')) {
                $this->forge->dropColumn('users', $column);
            }
        }
    }
}
