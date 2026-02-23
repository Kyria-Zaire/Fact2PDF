<?php
/**
 * Définition des routes de l'application web
 * Format : [METHOD, URI_pattern, Controller::method, [roles_autorisés]]
 * Les roles : 'admin', 'user', 'viewer' — null = public
 */

declare(strict_types=1);

return [
    // ---- Authentification (public) ----
    ['GET',  '/',         'AuthController@showLogin',   null],
    ['GET',  '/login',    'AuthController@showLogin',   null],
    ['POST', '/login',    'AuthController@login',       null],
    ['GET',  '/logout',   'AuthController@logout',      ['admin', 'user', 'viewer']],

    // ---- Dashboard ----
    ['GET',  '/dashboard', 'DashboardController@index', ['admin', 'user', 'viewer']],

    // ---- Clients (CRUD) ----
    ['GET',  '/clients',           'ClientController@index',   ['admin', 'user', 'viewer']],
    ['GET',  '/clients/create',    'ClientController@create',  ['admin', 'user']],
    ['POST', '/clients',           'ClientController@store',   ['admin', 'user']],
    ['GET',  '/clients/{id}',      'ClientController@show',    ['admin', 'user', 'viewer']],
    ['GET',  '/clients/{id}/edit', 'ClientController@edit',    ['admin', 'user']],
    ['POST', '/clients/{id}',      'ClientController@update',  ['admin', 'user']],
    ['POST', '/clients/{id}/delete','ClientController@delete', ['admin']],

    // ---- Factures (CRUD) ----
    ['GET',  '/invoices',              'InvoiceController@index',    ['admin', 'user', 'viewer']],
    ['GET',  '/invoices/create',       'InvoiceController@create',   ['admin', 'user']],
    ['POST', '/invoices',              'InvoiceController@store',    ['admin', 'user']],
    ['GET',  '/invoices/{id}',         'InvoiceController@show',     ['admin', 'user', 'viewer']],
    ['GET',  '/invoices/{id}/edit',    'InvoiceController@edit',     ['admin', 'user']],
    ['POST', '/invoices/{id}',         'InvoiceController@update',   ['admin', 'user']],
    ['POST', '/invoices/{id}/delete',  'InvoiceController@delete',   ['admin']],
    ['GET',  '/invoices/{id}/pdf',     'InvoiceController@pdf',      ['admin', 'user', 'viewer']],
    ['GET',  '/invoices/export/csv',   'InvoiceController@exportCsv',['admin', 'user']],

    // ---- Admin Panel ----
    ['GET',  '/admin',        'AdminController@index',      ['admin']],
    ['GET',  '/admin/users',  'AdminController@users',      ['admin']],
    ['POST', '/admin/users',  'AdminController@createUser', ['admin']],
];
