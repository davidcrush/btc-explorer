<?php

namespace App\DataTransferObjects;

final readonly class BtcTransactionDetailData
{
    /**
     * @param  list<array{txid: ?string, vout: ?int, address: ?string, value: int, is_coinbase: bool}>  $inputs
     * @param  list<array{address: ?string, value: int}>  $outputs
     */
    public function __construct(
        public string $txid,
        public bool $confirmed,
        public ?string $blockHash,
        public ?int $blockHeight,
        public int $fee,
        public int $size,
        public int $weight,
        public int $virtualSize,
        public bool $isCoinbase,
        public int $inputTotal,
        public int $outputTotal,
        public array $inputs,
        public array $outputs,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'txid' => $this->txid,
            'confirmed' => $this->confirmed,
            'block_hash' => $this->blockHash,
            'block_height' => $this->blockHeight,
            'fee' => $this->fee,
            'size' => $this->size,
            'weight' => $this->weight,
            'virtual_size' => $this->virtualSize,
            'is_coinbase' => $this->isCoinbase,
            'input_total' => $this->inputTotal,
            'output_total' => $this->outputTotal,
            'inputs' => $this->inputs,
            'outputs' => $this->outputs,
        ];
    }
}
