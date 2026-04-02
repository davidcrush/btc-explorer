<?php

namespace App\DataTransferObjects;

final readonly class BtcMempoolStatsData
{
    /**
     * @param  list<array{0: float|int, 1: int}>  $feeHistogram
     */
    public function __construct(
        public int $count,
        public int $vsize,
        public int $totalFee,
        public array $feeHistogram,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'count' => $this->count,
            'vsize' => $this->vsize,
            'total_fee' => $this->totalFee,
            'fee_histogram' => array_map(
                static fn (array $row): array => [(float) $row[0], (int) $row[1]],
                $this->feeHistogram
            ),
        ];
    }
}
