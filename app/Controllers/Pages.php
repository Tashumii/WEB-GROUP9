<?php

namespace App\Controllers;

use App\Libraries\SalesTrackerAuth;
use App\Exceptions\ApiException;
use Config\Database;

class Pages extends BaseController
{
    private SalesTrackerAuth $auth;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->auth = new SalesTrackerAuth(Database::connect(), service('session'), $this->request);
    }

    public function home()
    {
        return view('index', ['csrfToken' => $this->auth->csrfToken()]);
    }

    public function login()
    {
        if ($this->auth->currentUserId() !== null) {
            return redirect()->to($this->signedInDestination());
        }
        return view('login', ['csrfToken' => $this->auth->csrfToken()]);
    }

    public function loginSubmit()
    {
        if (! $this->auth->csrfTokenIsValid((string) $this->request->getPost('csrf_token'))) {
            return view('login', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'Your session expired. Please refresh the page and try again.']);
        }
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        if ($username === '' || $password === '') {
            return view('login', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'Please enter both username and password.']);
        }
        if ($this->auth->isLoginRateLimited($username)) {
            return view('login', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'Too many login attempts. Please try again in 15 minutes.']);
        }
        try {
            $result = $this->auth->authenticate($username, $password);
            $this->auth->recordAuthAttempt('login', $username, true);
            $this->auth->establishUserSession($result['user'], $result['memberships']);
            return redirect()->to($this->signedInDestination());
        } catch (\App\Exceptions\ApiException $e) {
            $this->auth->recordAuthAttempt('login', $username, false);
            return view('login', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => $e->getMessage()]);
        } catch (\Throwable $e) {
            log_message('error', '[SalesTracker] login failed: {message}', ['message' => $e->getMessage()]);
            return view('login', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'Connection error. Please try again.']);
        }
    }

    public function register()
    {
        if ($this->auth->currentUserId() !== null) {
            return redirect()->to($this->signedInDestination());
        }
        return view('register', ['csrfToken' => $this->auth->csrfToken()]);
    }

    public function registerSubmit()
    {
        if (! $this->auth->csrfTokenIsValid((string) $this->request->getPost('csrf_token'))) {
            return view('register', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'Your session expired. Please refresh the page and try again.']);
        }
        $username = trim((string) $this->request->getPost('username'));
        $email = trim((string) $this->request->getPost('email'));
        $email = mb_substr($email, 0, 254);
        $password = (string) $this->request->getPost('password');
        $confirm = (string) $this->request->getPost('confirm_password');
        if (strlen($username) < 3 || strlen($username) > 50 || ! preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
            return view('register', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'Please enter a valid username.']);
        }
        if ($email !== '' && (preg_match('/[\r\n]/', $email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            return view('register', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'Enter a valid email address.']);
        }
        if ($password !== $confirm) {
            return view('register', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'Passwords do not match.']);
        }
        if (strlen($password) < 8 || strlen($password) > 72 || strcasecmp($password, $username) === 0 || in_array(strtolower($password), ['password', '12345678', '123456789', 'qwerty', 'qwerty123', 'letmein', 'admin123', 'iloveyou', 'changeme'], true)) {
            return view('register', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'Choose a stronger password with at least 8 characters.']);
        }
        if ($this->auth->isRegisterRateLimited()) {
            return view('register', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'Too many accounts created from this location. Please try again later.']);
        }
        $db = Database::connect();
        if ($db->table('users')->where('username', $username)->countAllResults() > 0) {
            $this->auth->recordAuthAttempt('register', $username, false);
            return view('register', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'Username already taken.']);
        }
        if ($email !== '' && $db->table('users')->where('email', $email)->countAllResults() > 0) {
            $this->auth->recordAuthAttempt('register', $username, false);
            return view('register', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => 'That email address is already registered.']);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $db->table('users')->insert([
                'username' => $username,
                'email' => $email !== '' ? $email : null,
                'password_hash' => $hash,
                'is_active' => 1,
            ]);
            $userId = (int) $db->insertID();
            $this->auth->recordAuthAttempt('register', $username, true);
            $this->auth->establishUserSession([
                'id' => $userId, 'username' => $username, 'email' => $email, 'password_hash' => $hash, 'must_change_password' => 0,
            ], []);
            return redirect()->to(site_url('create-business'));
        } catch (\Throwable $e) {
            log_message('error', '[SalesTracker] register failed: {message}', ['message' => $e->getMessage()]);
            $message = 'Could not create your account. Please try again.';
            if ((string) $e->getCode() === '23000') {
                $message = stripos($e->getMessage(), 'email') !== false
                    ? 'That email address is already registered.'
                    : 'Username already taken.';
            }
            return view('register', ['csrfToken' => $this->auth->csrfToken(), 'errorMessage' => $message]);
        }
    }

    public function createBusiness()
    {
        if ($this->auth->currentUserId() === null) {
            return redirect()->to(site_url('login'));
        }
        $session = service('session');
        if ((int) ($session->get('must_change_password') ?? 0) === 1) {
            return redirect()->to(site_url('settings'));
        }
        $memberships = $this->auth->memberships((int) $session->get('user_id'));
        if ((int) ($session->get('business_id') ?? 0) > 0) {
            return redirect()->to(site_url('dashboard'));
        }
        if ($memberships !== []) {
            $this->auth->switchBusiness($memberships[0]['business_id']);
            return redirect()->to(site_url('dashboard'));
        }
        return view('CreateBusiness', [
            'csrfToken' => $this->auth->csrfToken(),
            'currentUsername' => esc((string) ($session->get('username') ?? 'User')),
        ]);
    }

    public function logout()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return redirect()->to(site_url('login'));
        }
        $token = (string) ($this->request->getPost('csrf_token') ?? '');
        if (! $this->auth->csrfTokenIsValid($token)) {
            return $this->response->setStatusCode(403)->setBody('Invalid session check. Please go back and try again.');
        }
        $this->auth->logout();
        return redirect()->to(site_url('login'));
    }

    public function dashboard()
    {
        return $this->businessPage('Dashboard', 'Dashboard', 'Overview of your shop activity', 'dashboard');
    }

    public function products()
    {
        return $this->businessPage('Products', 'Products & Services', 'Manage products, stock quantity, prices and availability', 'products');
    }

    public function services()
    {
        return $this->businessPage('Services', 'Services', 'Manage printing and other services, their prices and availability', 'services');
    }

    public function recordSales()
    {
        return $this->businessPage('RecordSales', 'Record Sale', 'Add items and save a new transaction', 'record');
    }

    public function sales()
    {
        return $this->businessPage('Sales', 'Sales history', 'Every recorded sale, with quantity, unit price and total', 'sales');
    }

    public function analytics()
    {
        return $this->businessPage('Analytics', 'Analytics', 'Sales trends and business performance insights', 'analytics');
    }

    public function settings()
    {
        if ($this->auth->currentUserId() === null) {
            return redirect()->to(site_url('login'));
        }
        $session = service('session');
        $businessId = (int) ($session->get('business_id') ?? 0);
        $role = $businessId > 0 ? $this->auth->membershipRole((int) $session->get('user_id'), $businessId) : null;
        return view('Settings', [
            'csrfToken' => $this->auth->csrfToken(),
            'pageTitle' => 'Settings',
            'pageSubtitle' => 'Manage your account, security, team, and businesses',
            'activePage' => 'settings',
            'currentRoleKey' => $role ?? '',
            'currentUsername' => (string) ($session->get('username') ?? 'User'),
            'businessName' => $businessId > 0 ? $this->businessName($businessId) : '',
        ]);
    }

    private function businessPage(string $viewName, string $title, string $subtitle, string $activePage)
    {
        if ($this->auth->currentUserId() === null) {
            return redirect()->to(site_url('login'));
        }
        $session = service('session');
        if ((int) ($session->get('must_change_password') ?? 0) === 1) {
            return redirect()->to(site_url('settings'));
        }
        try {
            $businessId = $this->auth->activeBusinessId();
        } catch (ApiException $e) {
            return redirect()->to(site_url('create-business'));
        }
        $role = $this->auth->membershipRole((int) $session->get('user_id'), $businessId) ?? '';
        if ($role === 'staff' && in_array($viewName, ['Dashboard', 'Sales', 'Analytics'], true)) {
            return redirect()->to(site_url('record-sale'));
        }
        return view($viewName, [
            'csrfToken' => $this->auth->csrfToken(),
            'pageTitle' => $title,
            'pageSubtitle' => $subtitle,
            'activePage' => $activePage,
            'currentRoleKey' => $role,
            'currentUsername' => (string) ($session->get('username') ?? 'User'),
            'businessName' => $this->businessName($businessId),
        ]);
    }

    private function businessName(int $businessId): string
    {
        $row = Database::connect()->table('businesses')->select('name')->where('id', $businessId)->get()->getRowArray();
        return (string) ($row['name'] ?? 'Business');
    }

    private function signedInDestination(): string
    {
        $session = service('session');
        if ((int) ($session->get('must_change_password') ?? 0) === 1) {
            return site_url('settings');
        }
        try {
            $this->auth->activeBusinessId();
            return site_url('dashboard');
        } catch (ApiException $e) {
            return site_url('create-business');
        }
    }
}
