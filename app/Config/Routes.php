<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Pages::home');

$routes->get('login', 'Pages::login');
$routes->post('login', 'Pages::loginSubmit');
$routes->get('register', 'Pages::register');
$routes->post('register', 'Pages::registerSubmit');
$routes->get('create-business', 'Pages::createBusiness');
$routes->post('logout', 'Pages::logout');
$routes->get('dashboard', 'Pages::dashboard');
$routes->get('products', 'Pages::products');
$routes->get('record-sale', 'Pages::recordSales');
$routes->get('services', 'Pages::services');
$routes->get('sales', 'Pages::sales');
$routes->get('analytics', 'Pages::analytics');
$routes->get('settings', 'Pages::settings');

$routes->get('api/auth', 'Api\\Auth::index');
$routes->match(['post', 'put', 'patch', 'delete'], 'api/auth', 'Api\\Auth::action');
$routes->match(['get', 'post', 'put', 'patch', 'delete'], 'api/businesses', 'Api\\Businesses::index');
$routes->match(['get', 'post', 'put', 'patch', 'delete'], 'api/sessions', 'Api\\Sessions::index');
$routes->match(['get', 'post', 'put', 'patch', 'delete'], 'api/users', 'Api\\Users::index');
$routes->match(['get', 'post', 'put', 'patch', 'delete'], 'api/products', 'Api\\Products::index');
$routes->match(['get', 'post', 'put', 'patch', 'delete'], 'api/services', 'Api\\Services::index');
$routes->match(['get', 'post', 'put', 'patch', 'delete'], 'api/sales', 'Api\\Sales::index');
$routes->match(['get', 'post', 'put', 'patch', 'delete'], 'api/record-sales', 'Api\\RecordSales::index');
$routes->match(['get', 'post', 'put', 'patch', 'delete'], 'api/analytics', 'Api\\Analytics::index');
$routes->match(['get', 'post', 'put', 'patch', 'delete'], 'api/ai', 'Api\\AI::index');
