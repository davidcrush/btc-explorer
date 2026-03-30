<?php

namespace App\Services;

use App\DataTransferObjects\BtcBlockData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class BlockstreamApiClient
{
    /**
     * @return list<BtcBlockData>
     */
    public function latestBlocks(int $limit = 10): array
    {
        $safeLimit = max(1, min($limit, 100));

        try {
            $cachedBlocks = Cache::store($this->cacheStore())->remember(
                $this->latestBlocksCacheKey($safeLimit),
                now()->addSeconds($this->cacheTtl()),
                fn (): array => $this->fetchLatestBlocksPayload($safeLimit),
            );
        } catch (Throwable) {
            // If cache is unavailable, still serve data directly from upstream.
            $cachedBlocks = $this->fetchLatestBlocksPayload($safeLimit);
        }

        return $this->hydrateBlocks($cachedBlocks);
    }

    /**
     * @return list<array{
     *     hash: string,
     *     weight: int,
     *     height: int,
     *     transactions: list<string>,
     *     timestamp: int,
     *     size: int,
     *     difficulty: string,
     *     nonce: int,
     *     merkle_root: string
     * }>
     */
    private function fetchLatestBlocksPayload(int $safeLimit): array
    {

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

            $mapped[] = [
                'hash' => (string) $block['id'],
                'weight' => (int) ($block['weight'] ?? 0),
                'height' => (int) ($block['height'] ?? 0),
                'transactions' => $this->fetchTransactionIds((string) $block['id']),
                'timestamp' => (int) ($block['timestamp'] ?? 0),
                'size' => (int) ($block['size'] ?? 0),
                'difficulty' => (string) ($block['difficulty'] ?? '0'),
                'nonce' => (int) ($block['nonce'] ?? 0),
                'merkle_root' => (string) ($block['merkle_root'] ?? ''),
            ];
        }

        return $mapped;
    }

    /**
     * @param  mixed  $cachedBlocks
     * @return list<BtcBlockData>
     */
    private function hydrateBlocks(mixed $cachedBlocks): array
    {
        if (! is_array($cachedBlocks)) {
            return [];
        }

        $blocks = [];

        foreach ($cachedBlocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $transactions = $block['transactions'] ?? [];

            if (! is_array($transactions)) {
                $transactions = [];
            }

            $blocks[] = new BtcBlockData(
                hash: (string) ($block['hash'] ?? ''),
                weight: (int) ($block['weight'] ?? 0),
                height: (int) ($block['height'] ?? 0),
                transactions: array_values(array_filter($transactions, static fn (mixed $txid): bool => is_string($txid))),
                timestamp: (int) ($block['timestamp'] ?? 0),
                size: (int) ($block['size'] ?? 0),
                difficulty: (string) ($block['difficulty'] ?? '0'),
                nonce: (int) ($block['nonce'] ?? 0),
                merkleRoot: (string) ($block['merkle_root'] ?? ''),
            );
        }

        return $blocks;
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

    private function cacheStore(): string
    {
        return (string) config('services.blockstream.cache_store', 'redis');
    }

    private function cacheTtl(): int
    {
        return (int) config('services.blockstream.cache_ttl', 30);
    }

    private function latestBlocksCacheKey(int $limit): string
    {
        return "btc:blockstream:v2:latest-blocks:limit:{$limit}";
    }
}
