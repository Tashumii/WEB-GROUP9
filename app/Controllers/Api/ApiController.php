<?php

namespace App\Controllers\Api;

use App\Exceptions\ApiException;
use App\Libraries\SalesTrackerAuth;
use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Throwable;

abstract class ApiController extends BaseController
{
    protected BaseConnection $db;
    protected SalesTrackerAuth $auth;
    private ?array $requestBody = null;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = Database::connect();
        $this->auth = new SalesTrackerAuth($this->db, service('session'), $this->request);
        date_default_timezone_set('Asia/Manila');
    }

    protected function dispatch(callable $callback, string $access = 'business', bool $allowPasswordChange = false)
    {
        try {
            $this->addSecurityHeaders();
            $this->db->query("SET time_zone = '+08:00'");
            $this->auth->enforceApiRateLimit();

            $method = $this->method();
            $body = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) ? $this->body() : [];
            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                if ($method !== 'DELETE' && ! str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'application/json')) {
                    throw new ApiException('Content-Type must be application/json.', 415);
                }
                $csrf = $this->request->getHeaderLine('X-CSRF-Token') ?: ($body['csrf_token'] ?? null);
                if (! $this->auth->csrfTokenIsValid($csrf)) {
                    throw new ApiException('Invalid or expired CSRF token. Please refresh the page.', 419);
                }
            }

            $userId = null;
            if ($access !== 'public') {
                $userId = $this->auth->requireUser();
            }

            $session = service('session');
            if (! $allowPasswordChange && (int) ($session->get('must_change_password') ?? 0) === 1) {
                throw new ApiException('You must change your password before continuing.', 403);
            }

            $businessId = null;
            if (in_array($access, ['business', 'owner'], true)) {
                $businessId = $this->auth->activeBusinessId();
            }
            if ($access === 'owner') {
                $this->auth->requireRole((int) $businessId, ['owner']);
            }

            return $callback($body, $userId, $businessId);
        } catch (ApiException $e) {
            $reason = $e->status === 419 ? 'Page Expired' : '';
            return $this->jsonError($e->getMessage(), $e->status, $reason);
        } catch (Throwable $e) {
            log_message('error', '[SalesTracker] {class}: {message} in {file}:{line}', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->jsonError('A database error occurred. Please try again later.', 500);
        }
    }

    protected function body(): array
    {
        if ($this->requestBody !== null) {
            return $this->requestBody;
        }
        $length = (int) $this->request->getHeaderLine('Content-Length');
        if ($length > 1048576) {
            throw new ApiException('Request body is too large.', 413);
        }
        $raw = $this->request->getBody();
        if (trim($raw) === '') {
            $this->requestBody = $this->request->getPost() ?? [];
            return $this->requestBody;
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw new ApiException('Invalid JSON body.', 400);
        }
        $this->requestBody = $decoded;
        return $this->requestBody;
    }

    protected function json(array $data, int $status = 200, string $reason = '')
    {
        return $this->response->setStatusCode($status, $reason)->setJSON($data);
    }

    protected function jsonError(string $message, int $status = 400, string $reason = '')
    {
        return $this->json(['success' => false, 'error' => $message], $status, $reason);
    }

    protected function inputString(array $body, string $key, int $maxLength = 255): string
    {
        $value = $body[$key] ?? '';
        return is_scalar($value) ? mb_substr(trim((string) $value), 0, $maxLength) : '';
    }

    protected function method(): string
    {
        $method = strtoupper($this->request->getMethod());
        return $method === 'HEAD' ? 'GET' : $method;
    }

    protected function inputInt(array $body, string $key): int
    {
        $value = filter_var($body[$key] ?? null, FILTER_VALIDATE_INT);
        return $value === false ? 0 : (int) $value;
    }

    protected function validateUsername(string $username): ?string
    {
        if (strlen($username) < 3 || strlen($username) > 50) {
            return 'Username must be between 3 and 50 characters.';
        }
        if (! preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
            return 'Username may only contain letters, numbers, dots, underscores, or dashes.';
        }
        return null;
    }

    protected function validatePassword(string $password, string $username = ''): ?string
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters.';
        }
        if (strlen($password) > 72) {
            return 'Password must be at most 72 characters.';
        }
        if ($username !== '' && strcasecmp($password, $username) === 0) {
            return 'Password must be different from your username.';
        }
        if (in_array(strtolower($password), ['password', '12345678', '123456789', 'qwerty', 'qwerty123', 'letmein', 'admin123', 'iloveyou', 'changeme'], true)) {
            return 'That password is too common. Choose something less guessable.';
        }
        return null;
    }

    protected function validateEmail(string $email): ?string
    {
        if ($email === '') {
            return null;
        }
        if (strlen($email) > 254) {
            return 'Email must be at most 254 characters.';
        }
        if (preg_match('/[\r\n]/', $email)) {
            return 'Enter a valid email address.';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'Enter a valid email address.';
        }
        return null;
    }

    protected function isValidDate(string $date): bool
    {
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    protected function requireAdmin(int $businessId): void
    {
        $this->auth->requireRole($businessId, ['owner', 'admin']);
    }

    private function addSecurityHeaders(): void
    {
        $this->response
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('Referrer-Policy', 'same-origin')
            ->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'")
            ->setHeader('Cross-Origin-Resource-Policy', 'same-origin');
    }
}
