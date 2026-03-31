<?php

namespace App\Http\Resources\Api\V1;

use App\DataTransferObjects\BtcTransactionDetailData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BtcTransactionDetailData */
class BtcTransactionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
