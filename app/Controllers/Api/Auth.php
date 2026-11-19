<?php

namespace App\Controllers\Api;

use App\Exceptions\ApiException;

class Auth extends ApiController
{
    public function index()
    {
        return $this->dispatch(function (array $body, ?int $requestUserId) {
            $userId = $this->auth->currentUserId();
            if ($userId === null) {
                return $this->json(['authenticated' => false, 'user' => null]);
            }

            $user = $this->db->query(
                'SELECT id, username, is_active, must_change_password FROM users WHERE id = ? LIMIT 1',
                [$userId]
            )->getRowArray();
            if (! $user || (int) $user['is_active'] !== 1) {
                $this->auth->destroySession();
                return $this->json(['authenticated' => false, 'user' => null]);
            }

            $memberships = $this->auth->memberships($userId);
            $activeBusinessId = (int) (service('session')->get('business_id') ?? 0);
            $activeRole = null;
            foreach ($memberships as $membership) {
                if ($membership['business_id'] === $activeBusinessId) {
                    $activeRole = $membership['role'];
                    break;
                }
            }
            if ($activeRole === null && $memberships !== []) {
                $activeBusinessId = $memberships[0]['business_id'];
                $activeRole = $memberships[0]['role'];
                service('session')->set(['business_id' => $activeBusinessId, 'role' => $activeRole]);
            } elseif ($memberships === []) {
                $activeBusinessId = 0;
                service('session')->remove(['business_id', 'role']);
            }

            return $this->json([
                'authenticated' => true,
                'user' => [
                    'id' => (int) $user['id'],
                    'username' => $user['username'],
                    'must_change_password' => (int) $user['must_change_password'],
                ],
                'active_business_id' => $activeBusinessId,
                'active_role' => $activeRole,
                'businesses' => $this->formatMemberships($memberships),
            ]);
        }, 'public', true);
    }

    public function action()
    {
        if ($this->method() !== 'POST') {
            return $this->jsonError('Method not allowed.', 405);
        }
        return $this->dispatch(function (array $body, ?int $userId) {
            $action = (string) ($body['action'] ?? '');
            if ($action === 'login') {
                return $this->login($body);
            }
            if ($action === 'register') {
                return $this->register($body);
            }
            $userId = $this->auth->requireUser();
            if ($action === 'logout') {
                $this->auth->logout();
                return $this->json(['success' => true, 'message' => 'Logged out successfully.']);
            }
            if ($action === 'switch_business') {
                $businessId = $this->inputInt($body, 'business_id');
                if ($businessId <= 0) {
                    throw new ApiException('A valid business_id is required.', 400);
                }
                if ((int) (service('session')->get('must_change_password') ?? 0) === 1) {
                    throw new ApiException('You must change your password before continuing.', 403);
                }
                $membership = $this->auth->switchBusiness($businessId);
                return $this->json([
                    'success' => true,
                    'active_business_id' => (int) $membership['business_id'],
                    'active_role' => $membership['role'],
                    'business' => ['id' => (int) $membership['business_id'], 'name' => $membership['business_name']],
                    'csrf_token' => $this->auth->csrfToken(),
                ]);
            }
            throw new ApiException('Unknown action.', 400);
        }, 'public', true);
    }

    private function login(array $body)
    {
        $username = $this->inputString($body, 'username', 50);
        $password = (string) ($body['password'] ?? '');
        if ($username === '' || $password === '') {
            throw new ApiException('Username and password are required.', 400);
        }
        if ($this->auth->isLoginRateLimited($username)) {
            throw new ApiException('Too many login attempts. Please try again in 15 minutes.', 429);
        }
        try {
            $result = $this->auth->authenticate($username, $password);
        } catch (ApiException $e) {
            $this->auth->recordAuthAttempt('login', $username, false);
            throw $e;
        }
        $this->auth->recordAuthAttempt('login', $username, true);
        $this->auth->establishUserSession($result['user'], $result['memberships']);
        return $this->json([
            'success' => true,
            'message' => 'Login successful.',
            'user' => [
                'id' => (int) $result['user']['id'],
                'username' => $result['user']['username'],
                'must_change_password' => (int) ($result['user']['must_change_password'] ?? 0),
            ],
            'active_business_id' => (int) (service('session')->get('business_id') ?? 0),
            'active_role' => service('session')->get('role'),
            'businesses' => $this->formatMemberships($result['memberships']),
        ]);
    }

    private function register(array $body)
    {
        $username = $this->inputString($body, 'username', 50);
        $password = (string) ($body['password'] ?? '');
        $confirmPassword = (string) ($body['confirm_password'] ?? '');
        if (($error = $this->validateUsername($username)) !== null) {
            throw new ApiException($error, 400);
        }
        if ($password !== $confirmPassword) {
            throw new ApiException('Passwords do not match.', 400);
        }
        if (($error = $this->validatePassword($password, $username)) !== null) {
            throw new ApiException($error, 400);
        }
        if ($this->auth->isRegisterRateLimited()) {
            throw new ApiException('Too many accounts created from this location. Please try again later.', 429);
        }

        if ($this->db->table('users')->where('username', $username)->countAllResults() > 0) {
            $this->auth->recordAuthAttempt('register', $username, false);
            throw new ApiException('Username already taken.', 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $this->db->table('users')->insert(['username' => $username, 'password_hash' => $hash, 'is_active' => 1]);
        } catch (\Throwable $e) {
            if ((string) $e->getCode() === '23000') {
                $this->auth->recordAuthAttempt('register', $username, false);
                throw new ApiException('Username already taken.', 409);
            }
            throw $e;
        }
        $newUserId = (int) $this->db->insertID();
        $this->auth->recordAuthAttempt('register', $username, true);
        $this->auth->establishUserSession([
            'id' => $newUserId,
            'username' => $username,
            'must_change_password' => 0,
            'password_hash' => $hash,
        ], []);

        return $this->json([
            'success' => true,
            'message' => 'Account created. Create your first business to continue.',
            'user' => ['id' => $newUserId, 'username' => $username, 'must_change_password' => 0],
            'active_business_id' => 0,
            'active_role' => null,
            'businesses' => [],
        ], 201);
    }

    private function formatMemberships(array $memberships): array
    {
        return array_map(static fn (array $membership): array => [
            'id' => (int) $membership['business_id'],
            'name' => $membership['business_name'],
            'role' => $membership['role'],
        ], $memberships);
    }
}
