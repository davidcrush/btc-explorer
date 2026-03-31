# btc-explorer

A small Bitcoin block explorer built on Laravel. It pulls recent blocks and block details from [Blockstream’s Esplora API](https://github.com/Blockstream/esplora/blob/master/API.md), caches responses in Redis, and serves a React (Inertia) UI with Chakra UI.

## Features

- **Latest blocks** — Paginated list (user preference: 10 / 25 / 50 / 100 per page), skeleton loading, relative timestamps with full datetime on hover, amount formatting (BTC / mBTC / μBTC / sat).
- **Block detail** — Prev/next navigation, paginated transactions, miner and fee summaries where derivable from the coinbase.
- **REST API** — Versioned JSON under `/api/v1/...` for the same data the UI uses.
- **Caching** — Block list and block detail payloads cached (separate TTLs for “hot” vs stable detail when a next block exists).

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

## Environment variables (Blockstream)

| Variable | Purpose | Default |
|----------|---------|---------|
| `BLOCKSTREAM_API_BASE_URL` | Esplora base URL | `https://blockstream.info/api` |
| `BLOCKSTREAM_API_TIMEOUT` | HTTP timeout (seconds) | `10` |
| `BLOCKSTREAM_CACHE_STORE` | Laravel cache store name for upstream data | `redis` |
| `BLOCKSTREAM_CACHE_TTL` | Latest-blocks list cache TTL (seconds) | `30` |
| `BLOCKSTREAM_BLOCK_DETAIL_HOT_TTL` | Detail TTL when tip / no next block (seconds) | `30` |
| `BLOCKSTREAM_BLOCK_DETAIL_STABLE_TTL` | Detail TTL when `next_block_hash` exists (seconds) | `86400` |

## Project structure

```
app/
├── DataTransferObjects/     # BtcBlockData, BtcBlockDetailData
├── Http/
│   ├── Controllers/Api/V1/  # BtcBlockController
│   ├── Requests/Api/V1/     # ListBtcBlocksRequest
│   └── Resources/Api/V1/    # JSON shape for list + detail
└── Services/
    └── BlockstreamApiClient.php   # Esplora HTTP, pagination, cache keys, enrichment

resources/js/
├── Pages/
│   ├── Home.jsx
│   └── Blocks/
│       ├── Index.jsx        # Latest blocks
│       └── Show.jsx         # Block detail + txs
├── Layouts/AppLayout.jsx    # Nav, theme toggle, profile preferences
├── contexts/UserPreferencesContext.jsx
└── utils/

routes/
├── web.php                  # Inertia pages: /, /blocks, /blocks/{hash}
└── api.php                  # Prefixed automatically with /api
```

## API

Base path: **`/api/v1`**.

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/v1/btc/blocks` | Latest blocks. Query: `limit` (optional, default 10, max 100). Esplora returns 10 blocks per upstream request; the client pages until `limit` is satisfied. |
| `GET` | `/api/v1/btc/blocks/{hash}` | Block detail. Query: `transactions_start`, `transactions_limit` (paged tx list). |

## Tests

```bash
composer test
# or
php artisan test
```

Feature tests fake Blockstream HTTP responses and assert API behavior and caching assumptions.

## License

This application scaffold follows Laravel’s MIT license; your own changes and deployment remain your responsibility. Third-party APIs (e.g. Blockstream) are subject to their own terms and rate limits.
