<?php

namespace App\Http\Resources\Api\V1;

use App\DataTransferObjects\BtcTransactionSummaryData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BtcTransactionSummaryData */
class BtcTransactionSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
