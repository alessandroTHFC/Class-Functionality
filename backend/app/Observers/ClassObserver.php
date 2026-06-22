<?php

namespace App\Observers;

use App\Models\SchoolClass;
use Illuminate\Support\Facades\Auth;

class ClassObserver
{
    /**
     * Set created_by_user_id automatically before a class is inserted.
     *
     * Design pattern: the Observer pattern decouples the "who created this" concern from
     * both the controller and the service. Neither needs to know about Auth — this fires
     * automatically on every SchoolClass::create() call.
     *
     * Laravel concept: the 'creating' event fires before the INSERT runs, so modifying
     * $class attributes here affects what gets written to the database.
     */
    public function creating(SchoolClass $class): void
    {
        // ??= only assigns if the value is currently null.
        // In tests, SchoolClassFactory sets created_by_user_id explicitly — we don't override it.
        // In real HTTP requests, the factory isn't involved, so Auth::id() provides the value.
        $class->created_by_user_id ??= Auth::id();
    }
}
