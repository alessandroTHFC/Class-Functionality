<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                                  => $this->id,
            'full_name'                           => $this->full_name,
            'given_name'                          => $this->given_name,
            'family_name'                         => $this->family_name,
            'year_level'                          => new YearLevelResource($this->whenLoaded('yearLevel')),
            'nccd_level'                          => $this->nccd_level?->value,
            'nccd_category'                       => $this->nccd_category?->value,
            'primary_disability'                  => $this->primary_disability,
            'primary_disability_level_formalised' => $this->primary_disability_level_formalised,
        ];
    }
}
