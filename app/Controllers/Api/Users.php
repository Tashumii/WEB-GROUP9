<?php

namespace App\Controllers\Api;

use App\Exceptions\ApiException;

class Users extends ApiController
{
    public function index()
    {
        $method = $this->method();
        if (! in_array($method, ['GET', 'POST'], true)) {
            return $this->jsonError('Method not allowed.', 405);
        }

        return $this->dispatch(function (array $body, ?int $userId) use ($method) {
            $userId = (int) $userId;
            if ((int) (service('session')->get('must_change_password') ?? 0) === 1
                && ($method !== 'POST' || ($body['action'] ?? '') !== 'change_password')) {
                throw new ApiException('You must change your password before continuing.', 403);
            }
            if ($method === 'GET') {
                $businessId = $this->auth->activeBusinessId();
                $this->auth->requireRole($businessId, ['owner']);
                $members = $this->db->query(
                    "SELECT m.user_id, m.role, m.is_active, m.created_at, u.username, u.is_active AS user_active, u.last_login_at
                     FROM memberships m JOIN users u ON u.id = m.user_id WHERE m.business_id = ? AND m.is_active = 1
                     ORDER BY FIELD(m.role, 'owner', 'admin', 'staff'), u.username",
                    [$businessId]
                )->getResultArray();
                return $this->json([
                    'success' => true,
                    'members' => array_map(static fn (array $member): array => [
                        'user_id' => (int) $member['user_id'],
                        'username' => $member['username'],
                        'role' => $member['role'],
                        'is_active' => (int) $member['is_active'],
                        'user_active' => (int) $member['user_active'],
                        'last_login_at' => $member['last_login_at'],
                        'created_at' => $member['created_at'],
                    ], $members),
                ]);
            }

            $action = (string) ($body['action'] ?? '');
            if ($action === 'change_password') {
                return $this->changePassword($body, $userId);
            }
            if ((int) (service('session')->get('must_change_password') ?? 0) === 1) {
                throw new ApiException('You must change your password before continuing.', 403);
            }
            $businessId = $this->auth->activeBusinessId();
            $this->auth->requireRole($businessId, ['owner']);

            return match ($action) {
                'create' => $this->createMember($body, $businessId, $userId),
                'update_role' => $this->updateRole($body, $businessId),
                'remove' => $this->removeMember($body, $businessId, $userId),
                'reset_password' => $this->resetMemberPassword($body, $businessId),
                default => throw new ApiException('Unknown action.', 400),
            };
        }, 'auth', true);
    }

    private function changePassword(array $body, int $userId)
    {
        $currentPassword = (string) ($body['current_password'] ?? '');
        $newPassword = (string) ($body['new_password'] ?? '');
        $confirmPassword = (string) ($body['confirm_password'] ?? '');
        if ($currentPassword === '') {
            throw new ApiException('Current password is required.', 400);
        }
        if ($newPassword !== $confirmPassword) {
            throw new ApiException('New passwords do not match.', 400);
        }
        $username = (string) (service('session')->get('username') ?? '');
        if (($error = $this->validatePassword($newPassword, $username)) !== null) {
            throw new ApiException($error, 400);
        }
        $row = $this->db->query('SELECT password_hash FROM users WHERE id = ? LIMIT 1', [$userId])->getRowArray();
        if (! $row || ! password_verify($currentPassword, (string) $row['password_hash'])) {
            throw new ApiException('Current password is incorrect.', 400);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->table('users')->where('id', $userId)->update([
            'password_hash' => $newHash,
            'must_change_password' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->auth->revokeUserSessions($userId, $this->auth->sessionHash());
        $previousHash = $this->auth->sessionHash();
        service('session')->set([
            'must_change_password' => 0,
            'auth_fingerprint' => hash('sha256', $newHash),
        ]);
        service('session')->regenerate(true);
        $this->auth->touchSession();
        $this->auth->registerPersistentSession($userId);
        $this->auth->revokeSessionHash($previousHash);
        $this->auth->rotateCsrfToken();
        return $this->json([
            'success' => true,
            'message' => 'Password updated. Your other devices have been signed out.',
            'csrf_token' => $this->auth->csrfToken(),
        ]);
    }

    private function createMember(array $body, int $businessId, int $ownerId)
    {
        $username = $this->inputString($body, 'username', 50);
        $password = (string) ($body['password'] ?? '');
        $confirmPassword = (string) ($body['confirm_password'] ?? '');
        $role = $this->inputString($body, 'role', 20);
        if (($error = $this->validateUsername($username)) !== null) {
            throw new ApiException($error, 400);
        }
        if (! in_array($role, ['admin', 'staff'], true)) {
            throw new ApiException('Role must be admin or staff.', 400);
        }
        if ($password !== $confirmPassword) {
            throw new ApiException('Passwords do not match.', 400);
        }
        if (($error = $this->validatePassword($password, $username)) !== null) {
            throw new ApiException($error, 400);
        }

        $existing = $this->db->table('users')->where('username', $username)->get()->getRowArray();
        if ($existing) {
            if ((int) $existing['is_active'] !== 1 || ! password_verify($password, (string) $existing['password_hash'])) {
                throw new ApiException('That account could not be added. Check the password or contact the administrator.', 409);
            }
            $membership = $this->teamMember($businessId, (int) $existing['id']);
            if ($membership && (int) $membership['is_active'] === 1) {
                throw new ApiException('That account is already part of this business.', 409);
            }
            if ($membership) {
                $this->db->table('memberships')->where('id', (int) $membership['membership_id'])->update([
                    'role' => $role,
                    'is_active' => 1,
                    'created_by' => $ownerId,
                ]);
            } else {
                $this->db->table('memberships')->insert([
                    'user_id' => (int) $existing['id'], 'business_id' => $businessId,
                    'role' => $role, 'created_by' => $ownerId,
                ]);
            }
            return $this->json([
                'success' => true,
                'message' => 'Existing account added to this business.',
                'user_id' => (int) $existing['id'],
            ]);
        }

        $this->db->transBegin();
        try {
            $this->db->table('users')->insert([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'is_active' => 1,
            ]);
            $newUserId = (int) $this->db->insertID();
            $this->db->table('memberships')->insert([
                'user_id' => $newUserId, 'business_id' => $businessId,
                'role' => $role, 'created_by' => $ownerId,
            ]);
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Unable to add team member.');
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            if ((string) $e->getCode() === '23000') {
                throw new ApiException('Username already taken.', 409);
            }
            throw $e;
        }
        return $this->json(['success' => true, 'message' => 'Team member created.', 'user_id' => $newUserId], 201);
    }

    private function updateRole(array $body, int $businessId)
    {
        $targetUserId = $this->inputInt($body, 'user_id');
        $role = $this->inputString($body, 'role', 20);
        if ($targetUserId <= 0 || ! in_array($role, ['admin', 'staff'], true)) {
            throw new ApiException('A valid user and role are required.', 400);
        }
        $member = $this->teamMember($businessId, $targetUserId);
        if (! $member || (int) $member['is_active'] !== 1) {
            throw new ApiException('Team member not found.', 404);
        }
        if ($member['role'] === 'owner') {
            throw new ApiException('The business owner role cannot be changed.', 403);
        }
        $this->db->table('memberships')->where('id', (int) $member['membership_id'])->update(['role' => $role]);
        return $this->json(['success' => true, 'message' => 'Role updated.']);
    }

    private function removeMember(array $body, int $businessId, int $ownerId)
    {
        $targetUserId = $this->inputInt($body, 'user_id');
        if ($targetUserId <= 0) {
            throw new ApiException('A valid user_id is required.', 400);
        }
        if ($targetUserId === $ownerId) {
            throw new ApiException('You cannot remove yourself from the business.', 400);
        }
        $member = $this->teamMember($businessId, $targetUserId);
        if (! $member || (int) $member['is_active'] !== 1) {
            throw new ApiException('Team member not found.', 404);
        }
        if ($member['role'] === 'owner') {
            throw new ApiException('The business owner cannot be removed.', 403);
        }
        $this->db->table('memberships')->where('id', (int) $member['membership_id'])->update(['is_active' => 0]);
        $remaining = $this->db->table('memberships')->where(['user_id' => $targetUserId, 'is_active' => 1])->countAllResults();
        if ($remaining === 0) {
            $this->auth->revokeUserSessions($targetUserId);
        }
        return $this->json(['success' => true, 'message' => 'Team member removed.']);
    }

    private function resetMemberPassword(array $body, int $businessId)
    {
        $targetUserId = $this->inputInt($body, 'user_id');
        $newPassword = (string) ($body['new_password'] ?? '');
        $confirmPassword = (string) ($body['confirm_password'] ?? '');
        if ($targetUserId <= 0) {
            throw new ApiException('A valid user_id is required.', 400);
        }
        if ($newPassword !== $confirmPassword) {
            throw new ApiException('Passwords do not match.', 400);
        }
        $member = $this->teamMember($businessId, $targetUserId);
        if (! $member || (int) $member['is_active'] !== 1) {
            throw new ApiException('Team member not found.', 404);
        }
        if ($member['role'] === 'owner') {
            throw new ApiException('Owner passwords cannot be reset from team management.', 403);
        }
        if (($error = $this->validatePassword($newPassword, (string) $member['username'])) !== null) {
            throw new ApiException($error, 400);
        }
        $membershipCount = $this->db->table('memberships')->where(['user_id' => $targetUserId, 'is_active' => 1])->countAllResults();
        if ($membershipCount > 1) {
            throw new ApiException('This account belongs to multiple businesses. The member must change their own password.', 409);
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->table('users')->where('id', $targetUserId)->update([
            'password_hash' => $hash,
            'must_change_password' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->auth->revokeUserSessions($targetUserId);
        return $this->json(['success' => true, 'message' => 'Password reset. The member must change it after signing in.']);
    }

    private function teamMember(int $businessId, int $userId): ?array
    {
        return $this->db->query(
            'SELECT m.id AS membership_id, m.user_id, m.role, m.is_active, u.username, u.is_active AS user_active,
             u.last_login_at, u.created_at FROM memberships m JOIN users u ON u.id = m.user_id
             WHERE m.business_id = ? AND m.user_id = ? LIMIT 1',
            [$businessId, $userId]
        )->getRowArray();
    }
}
