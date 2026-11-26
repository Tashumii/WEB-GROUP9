<?php

namespace App\Controllers\Api;

use App\Exceptions\ApiException;
use App\Libraries\TwoFactorAuth;

class Users extends ApiController
{
    private TwoFactorAuth $twoFactor;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->twoFactor = new TwoFactorAuth();
    }
    public function index()
    {
        $method = $this->method();
        if (! in_array($method, ['GET', 'POST'], true)) {
            return $this->jsonError('Method not allowed.', 405);
        }

        return $this->dispatch(function (array $body, ?int $userId) use ($method) {
            $userId = (int) $userId;
            $selfServiceAction = $method === 'POST' && in_array((string) ($body['action'] ?? ''), ['change_password', 'update_email', 'two_factor_setup', 'two_factor_enable', 'two_factor_disable', 'two_factor_regenerate_recovery', 'two_factor_status'], true);
            if ((int) (service('session')->get('must_change_password') ?? 0) === 1 && ! $selfServiceAction) {
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
            if ($action === 'update_email') {
                return $this->updateEmail($body, $userId);
            }
            if (str_starts_with($action, 'two_factor_')) {
                return $this->handleTwoFactor($action, $body, $userId);
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
        $email = $this->inputString($body, 'email', 254);
        $twoFactorCode = (string) ($body['two_factor_code'] ?? '');
        $recoveryCode = (string) ($body['recovery_code'] ?? '');
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
        if (($error = $this->validateEmail($email)) !== null) {
            throw new ApiException($error, 400);
        }
        $row = $this->db->query('SELECT username, email, password_hash, two_factor_enabled, two_factor_secret, two_factor_recovery_codes, two_factor_last_used FROM users WHERE id = ? LIMIT 1', [$userId])->getRowArray();
        if (! $row || ! password_verify($currentPassword, (string) $row['password_hash'])) {
            throw new ApiException('Current password is incorrect.', 400);
        }
        if (password_verify($newPassword, (string) $row['password_hash'])) {
            throw new ApiException('New password must be different from your current password.', 400);
        }

        // When the account has an email on file, re-enter it as a second
        // identity check. Accounts without one can add it from Settings.
        $accountEmail = trim((string) ($row['email'] ?? ''));
        if ($accountEmail !== '') {
            if ($email === '') {
                throw new ApiException('Enter your account email to confirm this change.', 400);
            }
            if (strcasecmp($email, $accountEmail) !== 0) {
                throw new ApiException('The email address does not match this account.', 400);
            }
        }

        // Two-factor check (if enabled).
        $this->db->transBegin();
        try {
        if ((int) ($row['two_factor_enabled'] ?? 0) === 1) {
            $valid2fa = false;
            $updatedRecoveryCodes = null;
            $newLastUsed = null;
            if ($recoveryCode !== '') {
                $hashes = json_decode((string) ($row['two_factor_recovery_codes'] ?? '[]'), true);
                if (! is_array($hashes)) {
                    $hashes = [];
                }
                $index = $this->twoFactor->verifyRecoveryCode($recoveryCode, $hashes);
                if ($index >= 0) {
                    $valid2fa = true;
                    array_splice($hashes, $index, 1);
                    $updatedRecoveryCodes = $hashes;
                }
            } else {
                try {
                    $secret = $this->twoFactor->decryptSecret((string) $row['two_factor_secret']);
                } catch (\Throwable $e) {
                    throw new ApiException('Two-factor configuration error. Contact an administrator.', 500);
                }
                $lastUsed = $row['two_factor_last_used'] !== null ? (int) $row['two_factor_last_used'] : null;
                $timestamp = $this->twoFactor->verifyCode($secret, $twoFactorCode, $lastUsed);
                if ($timestamp !== false) {
                    $valid2fa = true;
                    $newLastUsed = (int) $timestamp;
                }
            }
            if (! $valid2fa) {
                throw new ApiException('Invalid two-factor authentication code.', 400);
            }
            if ($newLastUsed !== null) {
                $this->db->table('users')->where('id', $userId)->update(['two_factor_last_used' => $newLastUsed]);
            }
            if ($updatedRecoveryCodes !== null) {
                $this->db->table('users')->where('id', $userId)->update(['two_factor_recovery_codes' => json_encode(array_values($updatedRecoveryCodes))]);
            }
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->table('users')->where('id', $userId)->update([
            'password_hash' => $newHash,
            'must_change_password' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
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

    private function updateEmail(array $body, int $userId)
    {
        $email = $this->inputString($body, 'email', 254);
        if (($error = $this->validateEmail($email)) !== null) {
            throw new ApiException($error, 400);
        }

        $storedEmail = $email === '' ? null : $email;
        if ($storedEmail !== null
            && $this->db->table('users')->where('email', $storedEmail)->where('id !=', $userId)->countAllResults() > 0) {
            throw new ApiException('That email address is already registered.', 409);
        }

        try {
            $this->db->table('users')->where('id', $userId)->update([
                'email' => $storedEmail,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            if ((string) $e->getCode() === '23000') {
                throw new ApiException('That email address is already registered.', 409);
            }
            throw $e;
        }

        return $this->json([
            'success' => true,
            'message' => $storedEmail === null ? 'Email removed.' : 'Email updated.',
            'email' => (string) ($storedEmail ?? ''),
        ]);
    }

    private function handleTwoFactor(string $action, array $body, int $userId)
    {
        return match ($action) {
            'two_factor_status' => $this->twoFactorStatus($userId),
            'two_factor_setup' => $this->twoFactorSetup($userId),
            'two_factor_enable' => $this->twoFactorEnable($body, $userId),
            'two_factor_disable' => $this->twoFactorDisable($body, $userId),
            'two_factor_regenerate_recovery' => $this->twoFactorRegenerateRecovery($body, $userId),
            default => throw new ApiException('Unknown action.', 400),
        };
    }

    private function twoFactorStatus(int $userId)
    {
        $row = $this->db->query(
            'SELECT two_factor_enabled, two_factor_recovery_codes, two_factor_confirmed_at FROM users WHERE id = ? LIMIT 1',
            [$userId]
        )->getRowArray();
        $codes = json_decode((string) ($row['two_factor_recovery_codes'] ?? '[]'), true);
        $remaining = is_array($codes) ? count($codes) : 0;
        return $this->json([
            'success' => true,
            'enabled' => (int) ($row['two_factor_enabled'] ?? 0) === 1,
            'recovery_codes_remaining' => $remaining,
            'confirmed_at' => $row['two_factor_confirmed_at'] ?? null,
        ]);
    }

    private function twoFactorSetup(int $userId)
    {
        $session = service('session');
        $secret = $this->twoFactor->generateSecret();
        $session->set([
            'pending_2fa_secret' => $secret,
            'pending_2fa_secret_expires' => time() + 300,
            'pending_2fa_secret_user' => $userId,
        ]);
        $user = $this->db->query('SELECT username FROM users WHERE id = ? LIMIT 1', [$userId])->getRowArray();
        $holder = (string) ($user['username'] ?? 'user');
        $otpAuthUrl = $this->twoFactor->getOtpAuthUrl('Sales Tracker', $holder, $secret);
        $qrSvg = $this->twoFactor->getQRCodeSvg($otpAuthUrl);
        return $this->json([
            'success' => true,
            'secret' => $secret,
            'otpauth_url' => $otpAuthUrl,
            'qr_svg' => $qrSvg,
        ]);
    }

    private function twoFactorEnable(array $body, int $userId)
    {
        $session = service('session');
        if ((int) ($session->get('pending_2fa_secret_user') ?? 0) !== $userId) {
            throw new ApiException('Start setup again before verifying.', 400);
        }
        if ((int) ($session->get('pending_2fa_secret_expires') ?? 0) < time()) {
            $session->remove(['pending_2fa_secret', 'pending_2fa_secret_expires', 'pending_2fa_secret_user']);
            throw new ApiException('Setup expired. Please start again.', 400);
        }
        $secret = (string) $session->get('pending_2fa_secret');
        $code = (string) ($body['code'] ?? '');
        if ($this->twoFactor->verifyCode($secret, $code) === false) {
            throw new ApiException('Invalid authentication code.', 400);
        }
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $hashed = array_map([$this->twoFactor, 'hashRecoveryCode'], $recoveryCodes);
        $this->db->table('users')->where('id', $userId)->update([
            'two_factor_secret' => $this->twoFactor->encryptSecret($secret),
            'two_factor_enabled' => 1,
            'two_factor_recovery_codes' => json_encode(array_values($hashed)),
            'two_factor_last_used' => null,
            'two_factor_confirmed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $session->remove(['pending_2fa_secret', 'pending_2fa_secret_expires', 'pending_2fa_secret_user']);
        return $this->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    private function twoFactorDisable(array $body, int $userId)
    {
        $password = (string) ($body['password'] ?? '');
        $code = (string) ($body['code'] ?? '');
        $row = $this->db->query(
            'SELECT password_hash, two_factor_enabled, two_factor_secret, two_factor_last_used FROM users WHERE id = ? LIMIT 1',
            [$userId]
        )->getRowArray();
        if (! $row || ! password_verify($password, (string) $row['password_hash'])) {
            throw new ApiException('Current password is incorrect.', 400);
        }
        if ((int) ($row['two_factor_enabled'] ?? 0) !== 1) {
            throw new ApiException('Two-factor authentication is not enabled.', 400);
        }
        try {
            $secret = $this->twoFactor->decryptSecret((string) $row['two_factor_secret']);
        } catch (\Throwable $e) {
            throw new ApiException('Two-factor configuration error. Contact an administrator.', 500);
        }
        $lastUsed = $row['two_factor_last_used'] !== null ? (int) $row['two_factor_last_used'] : null;
        if ($this->twoFactor->verifyCode($secret, $code, $lastUsed) === false) {
            throw new ApiException('Invalid authentication code.', 400);
        }
        $this->db->table('users')->where('id', $userId)->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => 0,
            'two_factor_recovery_codes' => null,
            'two_factor_last_used' => null,
            'two_factor_confirmed_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->json(['success' => true, 'message' => 'Two-factor authentication disabled.']);
    }

    private function twoFactorRegenerateRecovery(array $body, int $userId)
    {
        $password = (string) ($body['password'] ?? '');
        $code = (string) ($body['code'] ?? '');
        $row = $this->db->query(
            'SELECT password_hash, two_factor_enabled, two_factor_secret, two_factor_last_used FROM users WHERE id = ? LIMIT 1',
            [$userId]
        )->getRowArray();
        if (! $row || ! password_verify($password, (string) $row['password_hash'])) {
            throw new ApiException('Current password is incorrect.', 400);
        }
        if ((int) ($row['two_factor_enabled'] ?? 0) !== 1) {
            throw new ApiException('Two-factor authentication is not enabled.', 400);
        }
        try {
            $secret = $this->twoFactor->decryptSecret((string) $row['two_factor_secret']);
        } catch (\Throwable $e) {
            throw new ApiException('Two-factor configuration error. Contact an administrator.', 500);
        }
        $lastUsed = $row['two_factor_last_used'] !== null ? (int) $row['two_factor_last_used'] : null;
        if ($this->twoFactor->verifyCode($secret, $code, $lastUsed) === false) {
            throw new ApiException('Invalid authentication code.', 400);
        }
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $hashed = array_map([$this->twoFactor, 'hashRecoveryCode'], $recoveryCodes);
        $this->db->table('users')->where('id', $userId)->update([
            'two_factor_recovery_codes' => json_encode(array_values($hashed)),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->json([
            'success' => true,
            'message' => 'Recovery codes regenerated.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    private function createMember(array $body, int $businessId, int $ownerId)
    {        $username = $this->inputString($body, 'username', 50);
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
