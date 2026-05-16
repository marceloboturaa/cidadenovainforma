<?php

use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\BackupController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DocumentController;
use App\Controllers\Admin\EducationController;
use App\Controllers\Admin\ForumController;
use App\Controllers\Admin\InstitutionPageController;
use App\Controllers\Admin\LibraryEventController;
use App\Controllers\Admin\MenuController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\NewsController;
use App\Controllers\Admin\PersonController;
use App\Controllers\Admin\TagController;
use App\Controllers\Admin\UserController;
use App\Controllers\AuthController;
use App\Controllers\PublicController;

$router->get('/', [PublicController::class, 'home']);
$router->get('/eventos', [PublicController::class, 'events']);
$router->get('/eventos/futuros', [PublicController::class, 'upcomingEvents']);
$router->get('/eventos/realizados', [PublicController::class, 'pastEvents']);
$router->get('/evento/{id}', [PublicController::class, 'eventShow']);
$router->get('/instituicao', [PublicController::class, 'institution']);
$router->get('/instituicao/{slug}', [PublicController::class, 'institutionArea']);
$router->get('/documentos', [PublicController::class, 'documents']);
$router->get('/documentos/download', [PublicController::class, 'downloadDocument']);
$router->get('/buscar', [PublicController::class, 'search']);
$router->get('/reprise', [PublicController::class, 'archive']);
$router->get('/acervo', [PublicController::class, 'legacyArchive']);
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
$router->post('/admin/media/tinymce', [MediaController::class, 'tinyMceUpload']);
$router->get('/admin/password', [UserController::class, 'password']);
$router->post('/admin/password', [UserController::class, 'updatePassword']);
$router->get('/admin/users', [UserController::class, 'index']);
$router->post('/admin/users', [UserController::class, 'store']);
$router->post('/admin/users/update', [UserController::class, 'update']);
$router->post('/admin/users/status', [UserController::class, 'status']);
$router->post('/admin/users/registrations', [UserController::class, 'toggleRegistrations']);
$router->post('/admin/users/approve', [UserController::class, 'approve']);
$router->post('/admin/users/responsibilities', [UserController::class, 'responsibilities']);
$router->post('/admin/users/role', [UserController::class, 'updateRole']);
$router->post('/admin/users/reset-password', [UserController::class, 'resetPassword']);

$router->get('/admin/people', [PersonController::class, 'index']);
$router->get('/admin/people/edit', [PersonController::class, 'index']);
$router->post('/admin/people', [PersonController::class, 'store']);
$router->post('/admin/people/update', [PersonController::class, 'update']);
$router->post('/admin/people/delete', [PersonController::class, 'delete']);

$router->get('/admin/library-events', [LibraryEventController::class, 'index']);
$router->get('/admin/library-events/edit', [LibraryEventController::class, 'index']);
$router->post('/admin/library-events', [LibraryEventController::class, 'store']);
$router->post('/admin/library-events/update', [LibraryEventController::class, 'update']);
$router->post('/admin/library-events/delete', [LibraryEventController::class, 'delete']);
$router->get('/admin/library-events/participants', [LibraryEventController::class, 'participants']);
$router->post('/admin/library-events/participants', [LibraryEventController::class, 'addParticipant']);
$router->post('/admin/library-events/participants/remove', [LibraryEventController::class, 'removeParticipant']);

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

$router->get('/admin/education', [EducationController::class, 'index']);
$router->get('/admin/education/manage', [EducationController::class, 'manage']);
$router->post('/admin/education/course', [EducationController::class, 'storeCourse']);
$router->post('/admin/education/course/update', [EducationController::class, 'updateCourse']);
$router->post('/admin/education/course/delete', [EducationController::class, 'deleteCourse']);
$router->get('/admin/education/course', [EducationController::class, 'course']);
$router->post('/admin/education/module', [EducationController::class, 'storeModule']);
$router->post('/admin/education/module/update', [EducationController::class, 'updateModule']);
$router->post('/admin/education/module/delete', [EducationController::class, 'deleteModule']);
$router->post('/admin/education/lesson', [EducationController::class, 'storeLesson']);
$router->post('/admin/education/lesson/update', [EducationController::class, 'updateLesson']);
$router->post('/admin/education/lesson/delete', [EducationController::class, 'deleteLesson']);
$router->get('/admin/education/lesson', [EducationController::class, 'lesson']);
$router->post('/admin/education/forum/topic', [EducationController::class, 'storeForumTopic']);
$router->post('/admin/education/forum/topic/update', [EducationController::class, 'updateForumTopic']);
$router->post('/admin/education/forum/reply', [EducationController::class, 'storeForumReply']);
$router->post('/admin/education/forum/reply/delete', [EducationController::class, 'deleteForumReply']);
$router->post('/admin/education/forum/reply/restore', [EducationController::class, 'restoreForumReply']);
$router->post('/admin/education/forum/delete', [EducationController::class, 'deleteForumTopic']);
$router->post('/admin/education/block', [EducationController::class, 'storeBlock']);
$router->post('/admin/education/block/update', [EducationController::class, 'updateBlock']);
$router->post('/admin/education/block/delete', [EducationController::class, 'deleteBlock']);
$router->get('/admin/education/block/download', [EducationController::class, 'downloadBlock']);
$router->post('/admin/education/watch', [EducationController::class, 'watchVideo']);
$router->post('/admin/education/progress', [EducationController::class, 'progress']);
$router->get('/admin/education/attendance', [EducationController::class, 'attendance']);
$router->post('/admin/education/attendance', [EducationController::class, 'saveAttendance']);
$router->get('/admin/education/attendance/report', [EducationController::class, 'attendanceReport']);

$router->get('/admin/forum', [ForumController::class, 'index']);
$router->get('/admin/forum/area', [ForumController::class, 'area']);
$router->post('/admin/forum/topic', [ForumController::class, 'storeTopic']);
$router->get('/admin/forum/topic', [ForumController::class, 'topic']);
$router->post('/admin/forum/reply', [ForumController::class, 'reply']);
$router->post('/admin/forum/moderate', [ForumController::class, 'moderateTopic']);
$router->post('/admin/forum/reply/delete', [ForumController::class, 'deleteReply']);
$router->post('/admin/forum/category', [ForumController::class, 'category']);
$router->get('/admin/forum/attachment', [ForumController::class, 'download']);
