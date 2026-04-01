<?php

namespace App\DataTransferObjects;

final readonly class BtcTransactionSummaryData
{
    public function __construct(
        public string $txid,
        public int $fee,
    ) {}

    /**
     * @return array{txid: string, fee: int}
     */
    public function toArray(): array
    {
        return [
            'txid' => $this->txid,
            'fee' => $this->fee,
        ];
    }
}
