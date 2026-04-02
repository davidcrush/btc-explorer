# btc-explorer

A small Bitcoin block explorer built on Laravel. It pulls data from [Blockstream’s Esplora API](https://github.com/Blockstream/esplora/blob/master/API.md), caches responses in Redis, and serves a React (Inertia) UI with Chakra UI.

## Features

- **Latest blocks** — Paginated list (user preference: 10 / 25 / 50 / 100 per page), skeleton loading, relative timestamps with full datetime on hover, amount formatting (BTC / mBTC / μBTC / sat).
- **Block detail** — Prev/next navigation, paginated transactions (click a row for transaction detail), miner and fee summaries where derivable from the coinbase.
- **Transactions** — Search by 64-character hex txid; sample of up to ten recent **confirmed** transactions (from the chain tip block); full detail view with inputs, outputs, fee, size, vsize, and link to the confirming block when known.
- **Mempool** — Backlog stats from Esplora `GET /mempool` (count, vsize, total fees, fee histogram). Paginated list of **txids** from `GET /mempool/txids` only (no bulk `GET /tx/{txid}` calls for the list); open a row to load full transaction detail.
- **Home** — Summary of public JSON endpoints under `/api/v1`.
- **REST API** — Versioned JSON for the same data the UI uses.
- **Caching** — Block list, block detail, transaction detail, mempool stats, and mempool txid list use Redis (or your configured store) with “hot” vs stable TTLs where appropriate.
- **UI** — Dark theme only; header link to the GitHub repo; preferences (amount unit, blocks per page) under a settings control.

## Stack

| Layer | Technology |
|--------|------------|
| Backend | Laravel 13, PHP 8.3+ |
| HTTP / JSON API | Laravel routes, Form Requests, API Resources |
| Domain | Read-only DTOs + `BlockstreamApiClient` service |
| Frontend | Inertia.js + React 19, Chakra UI v3, Vite 8 |
| App DB | SQLite by default (sessions, etc.) |
| Upstream cache | Redis (recommended; configurable) |

## Prerequisites

- **PHP** 8.3 or newer with extensions Laravel expects (e.g. `mbstring`, `xml`, `ctype`, `json`, `openssl`, `pdo`, `tokenizer`).
- **Composer** 2.x
- **Node.js** 20+ (or current LTS) and **npm**
- **Redis** — Used as the cache store for Blockstream responses (`BLOCKSTREAM_CACHE_STORE=redis`). Run Redis locally, or use Docker / Laravel Sail (this repo’s `compose.yaml` includes Redis).

Optional: **Laravel Sail** (Docker) if you prefer containerized PHP, PostgreSQL, and Redis.

## Quick start

```bash
git clone <repository-url> btc-explorer
cd btc-explorer

cp .env.example .env
php artisan key:generate

composer install
npm install
npm run build

# SQLite (default): ensure the database file exists
touch database/database.sqlite
php artisan migrate

# Start Redis (example)
#   macOS: brew services start redis
#   Linux: sudo systemctl start redis
```

Configure `.env` at least for `APP_URL` and Redis if not using defaults:

```env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
BLOCKSTREAM_CACHE_STORE=redis
```

If Redis is unavailable, you can point Blockstream caching at another Laravel store (e.g. `file` or `database`) by changing `BLOCKSTREAM_CACHE_STORE`; ensure that store is configured and working.

## Run locally

**Option A — one command (PHP server, Vite, queue worker, logs)** — from `composer.json`:

```bash
composer run dev
```

Then open the URL shown by `php artisan serve` (typically `http://127.0.0.1:8000`).

**Option B — two terminals**

```bash
php artisan serve
```

```bash
npm run dev
```

Use `npm run build` for production assets; the app expects built assets when not running Vite.

## Docker (Laravel Sail)

This project includes a Sail-style `compose.yaml` (PHP app, PostgreSQL, Redis). With Sail installed and configured:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

Adjust `.env` for Sail (database, `REDIS_HOST`, etc.) per [Laravel Sail documentation](https://laravel.com/docs/sail).

Example Sail commands:

```bash
./vendor/bin/sail npm run build
./vendor/bin/sail artisan test
```

## Environment variables (Blockstream)

| Variable | Purpose | Default |
|----------|---------|---------|
| `BLOCKSTREAM_API_BASE_URL` | Esplora base URL | `https://blockstream.info/api` |
| `BLOCKSTREAM_API_TIMEOUT` | HTTP timeout (seconds) | `10` |
| `BLOCKSTREAM_CACHE_STORE` | Laravel cache store name for upstream data | `redis` |
| `BLOCKSTREAM_CACHE_TTL` | Latest-blocks list cache TTL (seconds) | `90` |
| `BLOCKSTREAM_BLOCK_DETAIL_HOT_TTL` | Detail / mempool-style “hot” TTL (seconds) | `30` |
| `BLOCKSTREAM_BLOCK_DETAIL_STABLE_TTL` | Block detail TTL when `next_block_hash` exists (seconds) | `86400` |

## Project structure

```
app/
├── DataTransferObjects/     # BtcBlockData, BtcBlockDetailData, transaction + mempool DTOs
├── Http/
│   ├── Controllers/Api/V1/  # BtcBlockController, BtcTransactionController, BtcMempoolController
│   ├── Requests/Api/V1/     # ListBtcBlocksRequest, ListMempoolTransactionsRequest
│   └── Resources/Api/V1/    # JSON shape for API responses
└── Services/
    └── BlockstreamApiClient.php   # Esplora HTTP, pagination, cache keys, enrichment

resources/js/
├── Pages/
│   ├── Home.jsx
│   ├── Blocks/            # Index, Show
│   ├── Transactions/      # Index (search + samples), Show
│   └── Mempool/Index.jsx
├── Layouts/AppLayout.jsx    # Nav, GitHub link, preferences, dark layout
├── contexts/UserPreferencesContext.jsx
└── utils/

routes/
├── web.php                  # Inertia: /, /blocks, /blocks/{hash}, /transactions, /transactions/{txid}, /mempool
└── api.php                  # Prefixed automatically with /api
```

## API

Base path: **`/api/v1`**.

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/v1/btc/blocks` | Latest blocks. Query: `limit` (default 10, max 100), `offset` (default 0, max 2000). Response: `data.blocks`, `data.has_more`. Returns **502** with a `message` and empty `data.blocks` when upstream fails. |
| `GET` | `/api/v1/btc/blocks/{hash}` | Block detail. Query: `transactions_start`, `transactions_limit` (1–25, default 25). |
| `GET` | `/api/v1/btc/transactions/recent` | Up to ten confirmed transaction summaries (`txid`, `fee`) from recent blocks. |
| `GET` | `/api/v1/btc/transactions/{txid}` | Full transaction (`txid` must be 64 hex chars). **404** if invalid or unknown; **502** on upstream failure. |
| `GET` | `/api/v1/btc/mempool/stats` | Mempool backlog from Esplora `GET /mempool`: `count`, `vsize`, `total_fee`, `fee_histogram`. |
| `GET` | `/api/v1/btc/mempool/transactions` | Paginated **txids** from cached `GET /mempool/txids` (no per-tx `GET /tx/...`). Query: `offset` (0–500000), `limit` (1–25, default 25). Response: `data.transactions` (`{ txid }` each), `data.total_count`, `data.offset`, `data.limit`, `data.has_more`. |

## Tests

```bash
composer test
# or
php artisan test
```

Feature tests fake Blockstream HTTP responses and cover blocks, transactions, and mempool APIs (`tests/Feature/BtcBlocksApiTest.php`, `BtcTransactionApiTest.php`, `BtcMempoolApiTest.php`).

## License

This application scaffold follows Laravel’s MIT license; your own changes and deployment remain your responsibility. Third-party APIs (e.g. Blockstream) are subject to their own terms and rate limits.
