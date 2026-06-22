<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentNoteResource extends JsonResource
{
    // The relationship on StudentNote is named schoolClass() — 'class' is a reserved
    // word in PHP and cannot be a method name. The JSON key is still 'class' to match
    // the API contract the frontend depends on.
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'note_text'             => $this->note_text,
            'note_date'             => $this->note_date?->toDateString(),
            'confidentiality_level' => $this->confidentiality_level,
            'author'                => $this->whenLoaded('author', fn () => [
                'id'   => $this->author->id,
                'name' => $this->author->name,
            ]),
            'class'                 => $this->whenLoaded('schoolClass', fn () => [
                'id'   => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
            ]),
            'created_at'            => $this->created_at?->toISOString(),
        ];
    }
}
