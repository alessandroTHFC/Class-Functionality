<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\YearLevelController;
use Illuminate\Support\Facades\Route;

// Public — no authentication required
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes — Sanctum validates the Bearer token on every request here.
// Any request without a valid token receives a 401 before reaching the controller.
Route::middleware('auth:sanctum')->group(function () {

    // Logout only needs auth — no tenant context required to revoke a token.
    Route::post('/logout', [AuthController::class, 'logout']);

    // Tenant-scoped routes — the InitialiseTenantFromUser middleware reads the authenticated
    // user's tenant_id, finds the tenant, and calls tenancy()->initialize($tenant).
    // All models using BelongsToTenant are then automatically scoped to that tenant
    // for the remainder of the request.
    Route::middleware('tenant')->group(function () {

        Route::get('/user', [AuthController::class, 'user']);

        // apiResource() registers the standard RESTful routes for a resource:
        //   GET    /classes          → index
        //   POST   /classes          → store
        //   GET    /classes/{class}  → show
        //   PUT    /classes/{class}  → update
        //   DELETE /classes/{class}  → destroy
        // Laravel uses route model binding to resolve {class} into a SchoolClass instance.
        // BelongsToTenant's global scope means a class from another tenant resolves to 404.
        Route::apiResource('classes', ClassController::class);

        // Year levels are a simple lookup list — no CRUD needed beyond index.
        Route::get('/year_levels', [YearLevelController::class, 'index']);
    });
});
