<?php

/**
 * eMunicipal — Web Routes
 *
 * All routes are registered here and dispatched by the Router.
 * $router is injected by Application::run() before this file is required.
 *
 * Route format:
 *   $router->get('/path', ControllerClass::class, 'methodName');
 *   $router->post('/path', ControllerClass::class, 'methodName');
 *
 * Middleware (auth, role checks) is applied inside controller methods,
 * not at the router level, keeping the router simple.
 */

use Emunicipal\Controllers\HomeController;
use Emunicipal\Controllers\AuthController;
use Emunicipal\Controllers\Resident\ResidentDashboardController;
use Emunicipal\Controllers\Resident\RequestController;
use Emunicipal\Controllers\Resident\ProfileController as ResidentProfileController;
use Emunicipal\Controllers\Resident\NotificationController;
use Emunicipal\Controllers\Staff\StaffDashboardController;
use Emunicipal\Controllers\Staff\StaffRequestController;
use Emunicipal\Controllers\Admin\AdminDashboardController;
use Emunicipal\Controllers\Admin\DocumentTypeController;
use Emunicipal\Controllers\Admin\RequirementController;
use Emunicipal\Controllers\Admin\UserController;
use Emunicipal\Controllers\Admin\AdminRequestController;
use Emunicipal\Controllers\FileController;
use Emunicipal\Controllers\Staff\StaffNotificationController;
use Emunicipal\Controllers\Admin\AdminNotificationController;
use Emunicipal\Controllers\Admin\ReportController;
use Emunicipal\Controllers\Admin\AuditLogController;
use Emunicipal\Controllers\Admin\SystemSettingController;
use Emunicipal\Controllers\Api\AuthApiController;
use Emunicipal\Controllers\Api\UserApiController;
use Emunicipal\Controllers\Api\DocumentTypeApiController;
use Emunicipal\Controllers\Api\RequirementApiController;
use Emunicipal\Controllers\Api\RequestApiController;
use Emunicipal\Controllers\Api\StatusApiController;
use Emunicipal\Controllers\Api\NotificationApiController;
use Emunicipal\Controllers\Resident\PaymentController;
use Emunicipal\Controllers\Staff\StaffPaymentController;

// ---------------------------------------------------------------------------
// PUBLIC ROUTES
// ---------------------------------------------------------------------------

$router->get('/',              HomeController::class, 'index');
$router->post('/',             HomeController::class, 'trackLookup');
$router->get('/about',         HomeController::class, 'about');
$router->get('/services',      HomeController::class, 'services');
$router->get('/how-it-works',  HomeController::class, 'howItWorks');
$router->get('/faqs',          HomeController::class, 'faqs');
$router->get('/contact',       HomeController::class, 'contact');
$router->post('/contact',      HomeController::class, 'sendContact');
$router->get('/track',         HomeController::class, 'trackPage');
$router->post('/track',        HomeController::class, 'trackLookup');

// Policy pages
$router->get('/policy/privacy',    HomeController::class, 'policyPrivacy');
$router->get('/policy/terms',      HomeController::class, 'policyTerms');
$router->get('/policy/moderation', HomeController::class, 'policyModeration');
$router->get('/policy/copyright',  HomeController::class, 'policyCopyright');

// ---------------------------------------------------------------------------
// AUTHENTICATION ROUTES
// ---------------------------------------------------------------------------

$router->get('/login',    AuthController::class, 'loginPage');
$router->post('/login',   AuthController::class, 'login');
$router->get('/register', AuthController::class, 'registerPage');
$router->post('/register', AuthController::class, 'register');
$router->post('/logout',  AuthController::class, 'logout');

// ---------------------------------------------------------------------------
// RESIDENT ROUTES
// ---------------------------------------------------------------------------

$router->get('/resident/dashboard',           ResidentDashboardController::class, 'index');
$router->get('/resident/requests',            RequestController::class,           'index');
$router->get('/resident/requests/new',        RequestController::class,           'newRequest');
$router->post('/resident/requests',           RequestController::class,           'store');
$router->get('/resident/requests/{id}',       RequestController::class,           'show');
$router->post('/resident/requests/{id}/cancel', RequestController::class,         'cancel');
$router->post('/resident/requests/{id}/documents', RequestController::class,      'uploadAdditionalDocuments');
$router->get( '/resident/requests/{id}/payment',          PaymentController::class,  'show');
$router->post('/resident/requests/{id}/payment',          PaymentController::class,  'selectCash');
$router->get( '/resident/requests/{id}/payment/online',   PaymentController::class,  'simulateOnline');
$router->post('/resident/requests/{id}/payment/online',   PaymentController::class,  'processOnline');
$router->get( '/resident/requests/{id}/payment/receipt',  PaymentController::class,  'receipt');
$router->get('/resident/track',               RequestController::class,           'track');
$router->get('/resident/notifications',       NotificationController::class,      'index');
$router->post('/resident/notifications/{id}/read', NotificationController::class, 'markRead');
$router->post('/resident/notifications/read-all',  NotificationController::class, 'markAllRead');
$router->get('/resident/profile',             ResidentProfileController::class,   'index');
$router->post('/resident/profile',            ResidentProfileController::class,   'update');
$router->post('/resident/profile/password',   ResidentProfileController::class,   'changePassword');

// ---------------------------------------------------------------------------
// STAFF ROUTES
// ---------------------------------------------------------------------------

$router->get('/staff/dashboard',              StaffDashboardController::class, 'index');
$router->get('/staff/requests',               StaffRequestController::class,   'index');
$router->get('/staff/requests/{id}',          StaffRequestController::class,   'show');
$router->post('/staff/requests/{id}/status',  StaffRequestController::class,   'updateStatus');
$router->post('/staff/requests/{id}/payment/confirm', StaffPaymentController::class, 'confirm');
$router->get('/staff/profile',                StaffDashboardController::class, 'profile');
$router->post('/staff/profile',               StaffDashboardController::class, 'updateProfile');
$router->post('/staff/profile/password',      StaffDashboardController::class, 'changePassword');

// Staff Notifications — read-all must come before {id}/read (Req 25.4, 25.8, 25.9)
$router->get('/staff/notifications',               StaffNotificationController::class, 'index');
$router->post('/staff/notifications/read-all',     StaffNotificationController::class, 'markAllRead');
$router->post('/staff/notifications/{id}/read',    StaffNotificationController::class, 'markRead');

// ---------------------------------------------------------------------------
// ADMIN ROUTES
// ---------------------------------------------------------------------------

$router->get('/admin/dashboard',                              AdminDashboardController::class, 'index');

// Document Types
$router->get('/admin/document-types',                         DocumentTypeController::class,   'index');
$router->get('/admin/document-types/create',                  DocumentTypeController::class,   'create');
$router->post('/admin/document-types',                        DocumentTypeController::class,   'store');
$router->get('/admin/document-types/{id}/edit',               DocumentTypeController::class,   'edit');
$router->post('/admin/document-types/{id}',                   DocumentTypeController::class,   'update');
$router->post('/admin/document-types/{id}/toggle',            DocumentTypeController::class,   'toggleActive');

// Requirements
$router->get('/admin/document-types/{typeId}/requirements',          RequirementController::class, 'index');
$router->get('/admin/document-types/{typeId}/requirements/create',   RequirementController::class, 'create');
$router->post('/admin/document-types/{typeId}/requirements',         RequirementController::class, 'store');
$router->get('/admin/document-types/{typeId}/requirements/{id}/edit', RequirementController::class, 'edit');
$router->post('/admin/document-types/{typeId}/requirements/{id}',    RequirementController::class, 'update');
$router->post('/admin/document-types/{typeId}/requirements/{id}/toggle', RequirementController::class, 'toggleActive');

// Users
$router->get('/admin/users',                                  UserController::class,           'index');
$router->get('/admin/users/create',                           UserController::class,           'create');
$router->post('/admin/users',                                 UserController::class,           'store');
$router->get('/admin/users/{id}',                             UserController::class,           'show');
$router->post('/admin/users/{id}/toggle',                     UserController::class,           'toggleActive');

// Admin profile
$router->get('/admin/profile',                                AdminDashboardController::class, 'profile');
$router->post('/admin/profile',                               AdminDashboardController::class, 'updateProfile');
$router->post('/admin/profile/password',                      AdminDashboardController::class, 'changePassword');

// Admin Requests
$router->get('/admin/requests',                               AdminRequestController::class,   'index');
$router->get('/admin/requests/{id}',                          AdminRequestController::class,   'show');
$router->post('/admin/requests/{id}/status',                  AdminRequestController::class,   'updateStatus');

// Admin Reports (Req 12.2–12.3)
$router->get('/admin/reports',                                ReportController::class,         'index');

// Admin Audit Logs (Req 12.4, 12.6, 13.3) — read-only, no POST/edit/delete routes
$router->get('/admin/audit-logs',                             AuditLogController::class,       'index');

// Admin System Settings (Req 20)
$router->get('/admin/settings',                               SystemSettingController::class,  'index');
$router->post('/admin/settings',                              SystemSettingController::class,  'update');

// Admin Notifications — read-all must come before {id}/read (Req 25.5, 25.8, 25.9)
$router->get('/admin/notifications',               AdminNotificationController::class, 'index');
$router->post('/admin/notifications/read-all',     AdminNotificationController::class, 'markAllRead');
$router->post('/admin/notifications/{id}/read',    AdminNotificationController::class, 'markRead');

// ---------------------------------------------------------------------------
// FILE DOWNLOAD ROUTE (secure, PHP-controlled)
// ---------------------------------------------------------------------------

$router->get('/files/{id}', FileController::class, 'download');

// ---------------------------------------------------------------------------
// RESTful API ROUTES (Req 15)
//
// Route registration ORDER matters — the router tries exact match first, then
// pattern match in registration order.  Specific paths (e.g. /api/requests/my)
// MUST be registered before parameterised ones (e.g. /api/requests/{id}).
// ---------------------------------------------------------------------------

// ── Authentication ──────────────────────────────────────────────────────────
$router->post('/api/auth/login',    AuthApiController::class, 'login');
$router->post('/api/auth/logout',   AuthApiController::class, 'logout');
$router->post('/api/auth/register', AuthApiController::class, 'register');

// ── Users ────────────────────────────────────────────────────────────────────
$router->get('/api/users',                  UserApiController::class, 'index');
$router->get('/api/users/{id}',             UserApiController::class, 'show');
$router->put('/api/users/{id}',             UserApiController::class, 'update');
$router->patch('/api/users/{id}/status',    UserApiController::class, 'toggleStatus');

// ── Document Types ────────────────────────────────────────────────────────────
$router->get('/api/document-types',                     DocumentTypeApiController::class, 'index');
$router->post('/api/document-types',                    DocumentTypeApiController::class, 'store');
$router->get('/api/document-types/{id}',                DocumentTypeApiController::class, 'show');
$router->put('/api/document-types/{id}',                DocumentTypeApiController::class, 'update');
$router->patch('/api/document-types/{id}/status',       DocumentTypeApiController::class, 'toggleStatus');

// ── Requirements ─────────────────────────────────────────────────────────────
// {typeId}/requirements/... must come before any {id} catch-all on document-types
$router->get('/api/document-types/{typeId}/requirements',     RequirementApiController::class, 'index');
$router->post('/api/document-types/{typeId}/requirements',    RequirementApiController::class, 'store');
$router->put('/api/requirements/{id}',                        RequirementApiController::class, 'update');
$router->patch('/api/requirements/{id}/status',               RequirementApiController::class, 'toggleStatus');

// ── Requests ──────────────────────────────────────────────────────────────────
// ORDERING: exact and sub-path routes before the {id} catch-all
$router->get('/api/requests',                               RequestApiController::class, 'index');
$router->post('/api/requests',                              RequestApiController::class, 'store');
$router->get('/api/requests/my',                            RequestApiController::class, 'my');
$router->get('/api/requests/{id}',                          RequestApiController::class, 'show');
$router->post('/api/requests/{id}/documents',               RequestApiController::class, 'uploadDocuments');
$router->patch('/api/requests/{id}/cancel',                 RequestApiController::class, 'cancel');

// ── Request Status ────────────────────────────────────────────────────────────
$router->patch('/api/requests/{id}/status',                 StatusApiController::class,  'updateStatus');
$router->get('/api/requests/{id}/history',                  StatusApiController::class,  'history');

// ── Notifications ─────────────────────────────────────────────────────────────
$router->get('/api/notifications',                          NotificationApiController::class, 'index');
$router->patch('/api/notifications/{id}/read',              NotificationApiController::class, 'markRead');

