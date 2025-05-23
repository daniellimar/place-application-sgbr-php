<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,

            'city' => [
                'id' => $this->city?->id,
                'name' => $this->city?->nome,
            ],

            'state' => [
                'id' => $this->state?->id,
                'name' => $this->state?->nome,
                'sigla' => $this->state?->sigla,
                'regiao_nome' => $this->state?->regiao_nome,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
