<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'year_level'     => new YearLevelResource($this->whenLoaded('yearLevel')),
            'created_by'     => [
                'id'   => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
            ],
            'assigned_users' => UserResource::collection($this->whenLoaded('users')),
            'student_count'  => $this->students_count,
        ];
    }
}
