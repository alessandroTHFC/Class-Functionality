<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ClassListCollection extends ResourceCollection
{
    public $collects = ClassListResource::class;

    public function __construct($resource, private readonly array $summary)
    {
        parent::__construct($resource);
    }

    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        $default['meta']['summary'] = $this->summary;

        return $default;
    }
}
