<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentListResource extends JsonResource
{
    /**
     * Shape the student record for the enrolment picker in the class form dialog.
     *
     * Only the fields the picker needs are included — full NCCD data is intentionally
     * absent here to keep the list response lean. The class detail view uses
     * ClassStudentResource for richer per-student data within a class context.
     *
     * full_name is computed by Student::getFullNameAttribute() — it is not a database column.
     * yearLevel is eager-loaded by StudentRepository::list() to avoid N+1 queries.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'full_name'   => $this->full_name,
            'given_name'  => $this->given_name,
            'family_name' => $this->family_name,
            'year_level'  => new YearLevelResource($this->whenLoaded('yearLevel')),
        ];
    }
}
