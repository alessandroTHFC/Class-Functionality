<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'email'  => $this->email,
            'roles'  => $this->getRoleNames(),
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id'   => $this->tenant->id,
                'name' => $this->tenant->name,
            ]),
        ];
    }
}
