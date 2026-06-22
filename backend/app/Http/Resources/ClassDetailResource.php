<?php

namespace App\Http\Resources;

use App\Enums\NccdLevelEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $students = $this->whenLoaded('students');

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'year_level'     => new YearLevelResource($this->whenLoaded('yearLevel')),
            'created_by'     => [
                'id'   => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
            ],
            'assigned_users' => UserResource::collection($this->whenLoaded('users')),
            'nccd_summary'   => $this->when($students instanceof \Illuminate\Support\Collection, fn () => [
                'QDTP'          => $students->filter(fn ($s) => $s->nccd_level === NccdLevelEnum::QDTP)->count(),
                'Supplementary' => $students->filter(fn ($s) => $s->nccd_level === NccdLevelEnum::Supplementary)->count(),
                'Substantial'   => $students->filter(fn ($s) => $s->nccd_level === NccdLevelEnum::Substantial)->count(),
                'Extensive'     => $students->filter(fn ($s) => $s->nccd_level === NccdLevelEnum::Extensive)->count(),
            ]),
            'students'       => ClassStudentResource::collection($students),
        ];
    }
}
