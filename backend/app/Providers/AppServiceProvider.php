<?php

namespace App\Providers;

use App\Models\SchoolClass;
use App\Models\StudentNote;
use App\Observers\ClassObserver;
use App\Policies\ClassPolicy;
use App\Policies\StudentNotePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Register the policy manually because the model is named SchoolClass but the
        // policy is named ClassPolicy — Laravel's auto-discovery expects SchoolClassPolicy,
        // so we map it explicitly here.
        Gate::policy(SchoolClass::class, ClassPolicy::class);

        // StudentNote also requires explicit policy registration — the model is StudentNote
        // but the policy is StudentNotePolicy, which Laravel can discover automatically.
        // Registered here for consistency and to make the mapping explicit.
        Gate::policy(StudentNote::class, StudentNotePolicy::class);

        // Register the observer so creating() fires automatically on every SchoolClass::create()
        // call, setting created_by_user_id without any controller or service needing to know about Auth.
        SchoolClass::observe(ClassObserver::class);
    }
}

