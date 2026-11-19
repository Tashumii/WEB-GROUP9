<?php

namespace App\Controllers\Api;

use App\Exceptions\ApiException;

class Sessions extends ApiController
{
    public function index()
    {
        if ($this->method() === 'GET') {
            return $this->dispatch(function (array $body, int $userId) {
                $rows = $this->db->query(
                    'SELECT id, ip_address, user_agent, created_at, last_seen_at, expires_at, session_hash
                     FROM user_sessions WHERE user_id = ? AND revoked_at IS NULL AND expires_at > NOW()
                     ORDER BY last_seen_at DESC, created_at DESC',
                    [$userId]
                )->getResultArray();
                $currentHash = $this->auth->sessionHash();
                return $this->json([
                    'success' => true,
                    'sessions' => array_map(static fn (array $row): array => [
                        'id' => (int) $row['id'],
                        'ip_address' => (string) $row['ip_address'],
                        'user_agent' => (string) $row['user_agent'],
                        'created_at' => (string) $row['created_at'],
                        'last_seen_at' => (string) $row['last_seen_at'],
                        'expires_at' => (string) $row['expires_at'],
                        'is_current' => hash_equals((string) $row['session_hash'], $currentHash),
                    ], $rows),
                ]);
            }, 'auth');
        }

        if ($this->method() !== 'POST') {
            return $this->jsonError('Method not allowed.', 405);
        }

        return $this->dispatch(function (array $body, int $userId) {
            $action = (string) ($body['action'] ?? '');
            if ($action === 'revoke') {
                $sessionId = $this->inputInt($body, 'session_id');
                if ($sessionId <= 0) {
                    throw new ApiException('A valid session_id is required.', 400);
                }
                $row = $this->db->query(
                    'SELECT id, session_hash FROM user_sessions WHERE id = ? AND user_id = ? LIMIT 1',
                    [$sessionId, $userId]
                )->getRowArray();
                if (! $row) {
                    throw new ApiException('Session not found.', 404);
                }
                $isCurrent = hash_equals((string) $row['session_hash'], $this->auth->sessionHash());
                $this->db->table('user_sessions')->where('id', (int) $row['id'])->update(['revoked_at' => date('Y-m-d H:i:s')]);
                if ($isCurrent) {
                    $this->auth->destroySession();
                }
                return $this->json([
                    'success' => true,
                    'message' => $isCurrent ? 'Current session signed out.' : 'Session signed out.',
                    'logged_out' => $isCurrent,
                ]);
            }
            if ($action === 'revoke_all') {
                $count = $this->auth->revokeUserSessions($userId, $this->auth->sessionHash());
                return $this->json(['success' => true, 'message' => 'All other sessions signed out.', 'revoked' => $count]);
            }
            throw new ApiException('Unknown action.', 400);
        }, 'auth');
    }
}
