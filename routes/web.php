<?php

use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\BackupController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DocumentController;
use App\Controllers\Admin\InstitutionPageController;
use App\Controllers\Admin\MenuController;
use App\Controllers\Admin\NewsController;
use App\Controllers\Admin\TagController;
use App\Controllers\Admin\UserController;
use App\Controllers\AuthController;
use App\Controllers\PublicController;

$router->get('/', [PublicController::class, 'home']);
$router->get('/instituicao', [PublicController::class, 'institution']);
$router->get('/instituicao/{slug}', [PublicController::class, 'institutionArea']);
$router->get('/documentos', [PublicController::class, 'documents']);
$router->get('/documentos/download', [PublicController::class, 'downloadDocument']);
$router->get('/buscar', [PublicController::class, 'search']);
$router->get('/acervo', [PublicController::class, 'archive']);
$router->get('/categoria', [PublicController::class, 'category']);
$router->get('/categoria/{slug}', [PublicController::class, 'category']);
$router->get('/tag', [PublicController::class, 'tag']);
$router->get('/tag/{slug}', [PublicController::class, 'tag']);
$router->get('/noticia', [PublicController::class, 'show']);
$router->get('/noticia/{slug}', [PublicController::class, 'show']);
$router->get('/sitemap.xml', [PublicController::class, 'sitemap']);
$router->get('/robots.txt', [PublicController::class, 'robots']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/forgot-password', [AuthController::class, 'showForgot']);
$router->post('/forgot-password', [AuthController::class, 'forgot']);
$router->get('/reset-password', [AuthController::class, 'showReset']);
$router->post('/reset-password', [AuthController::class, 'reset']);

$router->get('/admin', [DashboardController::class, 'index']);
$router->get('/admin/password', [UserController::class, 'password']);
$router->post('/admin/password', [UserController::class, 'updatePassword']);
$router->get('/admin/users', [UserController::class, 'index']);
$router->post('/admin/users', [UserController::class, 'store']);
$router->post('/admin/users/registrations', [UserController::class, 'toggleRegistrations']);
$router->post('/admin/users/approve', [UserController::class, 'approve']);
$router->post('/admin/users/responsibilities', [UserController::class, 'responsibilities']);
$router->post('/admin/users/role', [UserController::class, 'updateRole']);
$router->post('/admin/users/reset-password', [UserController::class, 'resetPassword']);

$router->get('/admin/institution-pages', [InstitutionPageController::class, 'index']);
$router->get('/admin/institution-pages/edit', [InstitutionPageController::class, 'edit']);
$router->post('/admin/institution-pages/update', [InstitutionPageController::class, 'update']);

$router->get('/admin/news', [NewsController::class, 'index']);
$router->get('/admin/news/create', [NewsController::class, 'create']);
$router->post('/admin/news', [NewsController::class, 'store']);
$router->get('/admin/news/edit', [NewsController::class, 'edit']);
$router->post('/admin/news/update', [NewsController::class, 'update']);
$router->post('/admin/news/approve', [NewsController::class, 'approve']);
$router->post('/admin/news/reject', [NewsController::class, 'reject']);
$router->post('/admin/news/archive', [NewsController::class, 'archive']);
$router->post('/admin/news/delete', [NewsController::class, 'delete']);
$router->post('/admin/news/bulk', [NewsController::class, 'bulk']);

$router->get('/admin/categories', [CategoryController::class, 'index']);
$router->get('/admin/categories/edit', [CategoryController::class, 'index']);
$router->post('/admin/categories', [CategoryController::class, 'store']);
$router->post('/admin/categories/update', [CategoryController::class, 'update']);
$router->post('/admin/categories/delete', [CategoryController::class, 'delete']);

$router->get('/admin/tags', [TagController::class, 'index']);
$router->get('/admin/tags/edit', [TagController::class, 'index']);
$router->post('/admin/tags', [TagController::class, 'store']);
$router->post('/admin/tags/update', [TagController::class, 'update']);
$router->post('/admin/tags/delete', [TagController::class, 'delete']);

$router->get('/admin/menu', [MenuController::class, 'index']);
$router->get('/admin/menu/edit', [MenuController::class, 'index']);
$router->post('/admin/menu', [MenuController::class, 'store']);
$router->post('/admin/menu/update', [MenuController::class, 'update']);
$router->post('/admin/menu/delete', [MenuController::class, 'delete']);

$router->get('/admin/backups', [BackupController::class, 'index']);
$router->post('/admin/backups/download', [BackupController::class, 'download']);
$router->post('/admin/backups/import', [BackupController::class, 'importFull']);
$router->post('/admin/backups/news/export', [BackupController::class, 'exportNews']);
$router->post('/admin/backups/news/import', [BackupController::class, 'importNews']);

$router->get('/admin/documents', [DocumentController::class, 'index']);
$router->post('/admin/documents', [DocumentController::class, 'store']);
$router->post('/admin/documents/formats', [DocumentController::class, 'formats']);
$router->get('/admin/documents/download', [DocumentController::class, 'download']);
$router->post('/admin/documents/access', [DocumentController::class, 'access']);
$router->post('/admin/documents/delete', [DocumentController::class, 'delete']);
