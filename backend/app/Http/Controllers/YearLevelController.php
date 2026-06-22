<?php

namespace App\Http\Controllers;

use App\Http\Resources\YearLevelResource;
use App\Models\YearLevel;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class YearLevelController extends Controller
{
    /**
     * Return all year levels for the current tenant, ordered for display.
     *
     * This endpoint exists solely to populate the year level filter dropdown on the
     * class dashboard. No pagination is needed — year levels are a small, stable list.
     *
     * Laravel concept: YearLevel uses BelongsToTenant, so the global scope on the model
     * automatically restricts the query to the current tenant's year levels. No manual
     * tenant_id filtering is needed here.
     *
     * ResourceCollection::collection() wraps the result in a { data: [...] } envelope
     * automatically, consistent with all other list responses in the API.
     */
    public function index(): AnonymousResourceCollection
    {
        $yearLevels = YearLevel::orderBy('sort_order')->get();

        return YearLevelResource::collection($yearLevels);
    }
}
