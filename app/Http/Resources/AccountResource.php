<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Account */
class AccountResource extends JsonResource
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
            'currency_code' => $this->currency->code,
            'currency_name' => $this->currency->name,
            'currency_symbol' => $this->currency->symbol,
            'currency_type' => $this->currency->type,
            'amount' => $this->amount,
        ];
    }
}
