<?php

namespace App\Controllers\Api;

use App\Exceptions\ApiException;

class Businesses extends ApiController
{
    public function index()
    {
        $method = $this->method();
        if ($method === 'GET') {
            return $this->dispatch(function (array $body, int $userId) {
                $memberships = $this->auth->memberships($userId);
                return $this->json([
                    'success' => true,
                    'active_business_id' => (int) (service('session')->get('business_id') ?? 0),
                    'businesses' => array_map(static fn (array $membership): array => [
                        'id' => (int) $membership['business_id'],
                        'name' => $membership['business_name'],
                        'role' => $membership['role'],
                    ], $memberships),
                ]);
            }, 'auth');
        }

        if ($method !== 'POST') {
            return $this->jsonError('Method not allowed.', 405);
        }

        return $this->dispatch(function (array $body, int $userId) {
            if (($body['action'] ?? '') !== 'create') {
                throw new ApiException('Unknown action.', 400);
            }
            $name = $this->inputString($body, 'name', 255);
            if ($name === '') {
                throw new ApiException('Business name is required.', 400);
            }

            $this->db->transBegin();
            $this->db->table('businesses')->insert(['name' => $name]);
            $businessId = (int) $this->db->insertID();
            $this->db->table('memberships')->insert([
                'user_id' => $userId,
                'business_id' => $businessId,
                'role' => 'owner',
                'created_by' => $userId,
            ]);
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                throw new \RuntimeException('Unable to create business.');
            }
            $this->db->transCommit();

            $this->auth->switchBusiness($businessId);
            return $this->json([
                'success' => true,
                'message' => 'Business created.',
                'business' => ['id' => $businessId, 'name' => $name, 'role' => 'owner'],
                'active_business_id' => $businessId,
                'active_role' => 'owner',
                'businesses' => array_map(static fn (array $item): array => [
                    'id' => (int) $item['business_id'],
                    'name' => $item['business_name'],
                    'role' => $item['role'],
                ], $this->auth->memberships($userId)),
                'csrf_token' => $this->auth->csrfToken(),
            ], 201);
        }, 'auth');
    }
}
