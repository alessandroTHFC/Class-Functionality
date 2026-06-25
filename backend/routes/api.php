<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
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

        // Staff users for the class filter dropdown and form dialog checklist.
        // All authenticated roles can call this — see UserController::index().
        Route::get('/users', [UserController::class, 'index']);

        // Students are seeded only — no create/update/delete endpoints exist.
        // This list endpoint is used exclusively by the class form dialog's enrolment picker.
        // Supports optional search, year_level_id, and per_page query parameters.
        Route::get('/students', [StudentController::class, 'index']);

        // Notes are nested under /students/{student} for the GET endpoint because notes are
        // a sub-resource of a student. {student} is resolved via route model binding —
        // BelongsToTenant's global scope means a student from another tenant returns 404
        // automatically, protecting cross-tenant access without any controller code.
        //
        // POST /notes is a flat route because a single request creates notes for multiple
        // students — nesting it under one student ID would be misleading for a bulk operation.
        Route::get('/students/{student}/notes', [NoteController::class, 'index']);
        Route::post('/notes', [NoteController::class, 'store']);
    });
});
