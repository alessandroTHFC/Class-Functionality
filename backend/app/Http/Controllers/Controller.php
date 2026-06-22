<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // AuthorizesRequests was removed from the base Controller in Laravel 11 to keep it lean.
    // Adding it back here gives all controllers access to $this->authorize(), which calls
    // the Gate and runs the registered Policy for the given model and action.
    use AuthorizesRequests;
}
