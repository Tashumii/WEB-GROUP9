<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Adds the optional account email column to databases created before it existed. */
class AddUserEmail extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('email', 'users')) {
            $this->forge->addColumn('users', [
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 254,
                    'null' => true,
                    'after' => 'username',
                ],
            ]);
        }

        if (! $this->hasUniqueEmailIndex()) {
            try {
                $this->db->query('ALTER TABLE users ADD UNIQUE KEY uk_users_email (email)');
            } catch (\Throwable $e) {
                // Duplicate addresses may already exist in a legacy database.
                // Leave the column in place without the unique index rather
                // than failing the whole migration.
                log_message('warning', '[SalesTracker] Could not add unique email index: {message}', ['message' => $e->getMessage()]);
            }
        }
    }

    public function down()
    {
        if ($this->hasUniqueEmailIndex()) {
            $this->db->query('ALTER TABLE users DROP INDEX uk_users_email');
        }
        if ($this->db->fieldExists('email', 'users')) {
            $this->forge->dropColumn('users', 'email');
        }
    }

    private function hasUniqueEmailIndex(): bool
    {
        $rows = $this->db->query('SHOW INDEX FROM users WHERE Column_name = ?', ['email'])->getResultArray();
        foreach ($rows as $row) {
            if ((int) ($row['Non_unique'] ?? 1) === 0) {
                return true;
            }
        }
        return false;
    }
}
