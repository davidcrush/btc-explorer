<?php

namespace App\Services;

use App\DataTransferObjects\BtcBlockData;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BlockstreamApiClient
{
    /**
     * @return list<BtcBlockData>
     */
    public function latestBlocks(int $limit = 10): array
    {
        $safeLimit = min($limit, 100);

        $blocksResponse = Http::baseUrl($this->baseUrl())
            ->timeout($this->timeout())
            ->acceptJson()
            ->get('/blocks');

        if (! $blocksResponse->successful()) {
            throw new RuntimeException('Unable to fetch latest blocks from Blockstream.');
        }

        $blocks = $blocksResponse->json();

        if (! is_array($blocks)) {
            throw new RuntimeException('Unexpected Blockstream blocks response format.');
        }

        $mapped = [];

        foreach (array_slice($blocks, 0, $safeLimit) as $block) {
            if (! is_array($block) || ! isset($block['id']) || ! is_string($block['id'])) {
                continue;
            }

            $mapped[] = new BtcBlockData(
                hash: (string) $block['id'],
                weight: (int) ($block['weight'] ?? 0),
                height: (int) ($block['height'] ?? 0),
                transactions: $this->fetchTransactionIds((string) $block['id']),
                timestamp: (int) ($block['timestamp'] ?? 0),
                size: (int) ($block['size'] ?? 0),
                difficulty: (string) ($block['difficulty'] ?? '0'),
                nonce: (int) ($block['nonce'] ?? 0),
                merkleRoot: (string) ($block['merkle_root'] ?? ''),
            );
        }

        return $mapped;
    }

    /**
     * @return list<string>
     */
    private function fetchTransactionIds(string $blockHash): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->timeout($this->timeout())
            ->acceptJson()
            ->get("/block/{$blockHash}/txids");

        if (! $response->successful()) {
            return [];
        }

        $txids = $response->json();

        if (! is_array($txids)) {
            return [];
        }

        return array_slice(
            array_values(array_filter($txids, static fn (mixed $txid): bool => is_string($txid))),
            0,
            25
        );
    }

    private function baseUrl(): string
    {
        return (string) config('services.blockstream.base_url', 'https://blockstream.info/api');
    }

    private function timeout(): int
    {
        return (int) config('services.blockstream.timeout', 10);
    }
}
