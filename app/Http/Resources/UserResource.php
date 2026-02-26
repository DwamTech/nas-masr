<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // 'UserName' => $this->name,
            'phone' => $this->phone,
            'role'=>$this->role,
            'is_representative' => $this->is_representative ?? false,
            'referral_code' => $this->referral_code??null,
            'country_code' => $this->country_code??null,
            'created_at' => $this->created_at?->format('Y-m-d H:i'),

        ];
    }
}
