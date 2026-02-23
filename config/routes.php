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
    ['GET',  '/invoices/export/xlsx',  'InvoiceController@exportXlsx',['admin', 'user']],
    ['GET',  '/invoices/export/csv',   'InvoiceController@exportCsv', ['admin', 'user']],

    // ---- Projets (CRUD + timeline AJAX) ----
    ['GET',  '/projects',                    'ProjectController@index',         ['admin', 'user', 'viewer']],
    ['GET',  '/projects/create',             'ProjectController@create',        ['admin', 'user']],
    ['POST', '/projects',                    'ProjectController@store',         ['admin', 'user']],
    ['GET',  '/projects/{id}',               'ProjectController@show',          ['admin', 'user', 'viewer']],
    ['GET',  '/projects/{id}/edit',          'ProjectController@edit',          ['admin', 'user']],
    ['POST', '/projects/{id}',               'ProjectController@update',        ['admin', 'user']],
    ['POST', '/projects/{id}/delete',        'ProjectController@delete',        ['admin']],
    ['POST', '/projects/{id}/timeline',      'ProjectController@updateTimeline',['admin', 'user']],

    // ---- Notifications (polling) ----
    ['GET',  '/notifications/poll',          'NotificationController@poll',        ['admin', 'user', 'viewer']],
    ['POST', '/notifications/{id}/read',     'NotificationController@markRead',    ['admin', 'user', 'viewer']],
    ['POST', '/notifications/read-all',      'NotificationController@markAllRead', ['admin', 'user', 'viewer']],

    // ---- Admin Panel ----
    ['GET',  '/admin',        'AdminController@index',      ['admin']],
    ['GET',  '/admin/users',  'AdminController@users',      ['admin']],
    ['POST', '/admin/users',  'AdminController@createUser', ['admin']],
];
