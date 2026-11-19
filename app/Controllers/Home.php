<?php

namespace App\Controllers;

use App\Libraries\SalesTrackerAuth;

class Home extends BaseController
{
    public function index(): string
    {
        $auth = new SalesTrackerAuth(null, service('session'), $this->request);
        return view('index', ['csrfToken' => $auth->csrfToken()]);
    }
}
