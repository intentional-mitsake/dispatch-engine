# dispatch-engine

When you submit an order on a major platform, a queue runs in the background — charging cards, reserving stock, sending confirmations. This project builds that queue from scratch without Laravel's built-in queue system. Every component is hand-built and every claim is verifiable.

**Built for learning. Designed with production principles.**

---

## What this is

A job queue built on PostgreSQL and Redis, using Laravel only for routing, and CLI commands. The project simulates an e-commerce checkout flow using two job types, each demonstrating a different concurrency problem.

---

## The e-commerce flow

```text
User checks out
      │
      ▼
[payment]                — charge customer
      │                    idempotency: PaymentRecord prevents double charging
      ▼
[inventory_reservation]  — reserve stock atomically
                           race condition: atomic UPDATE prevents overselling
```

Each job demonstrates a different concurrency concern.

---

## Key concepts

| Component | File | Core concept |
|---|---:|---|
| Schema | `dispatches` migration | Required job state |
| Atomic claiming | `DispatchClaimer` | Avoiding race conditions |
| Failure + backoff | `FailureHandler` | Exponential backoff with jitter |
| Dead-lettering | `status = dead` | Preserving exhausted jobs |
| Crash recovery | `WatchdogCommand` | At-least-once delivery |
| API idempotency | `POST /dispatches` | Entry-point deduplication |
| Handler idempotency | `payment_records` check | Safe repeated side effects |
| Inventory reservation | `InventoryHandler` | Atomic conditional updates |
| Rate limiting | `RateLimiter` Lua script | Atomic counters |
| Load testing | `LoadTest` command | Throughput measurement |
| Dashboard | `/api/stats` + Chart.js | Queue metrics |

---

## Architecture

```text
                           CLIENTS
                               │
                               ▼
                    ┌─────────────────┐
                    │    HTTP Layer   │
                    │ POST /dispatches│
                    │ GET /stats      │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │ Controllers     │
                    │                 │
                    │ • Validation    │
                    │ • Redis rate    │
                    │   limiter call  │
                    │ • API-level     │
                    │   idempotency   │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │ PostgreSQL      │
                    │ dispatches      │
                    │                 │
                    │ status          │
                    │ payload         │
                    │ attempts        │
                    │ available_at    │
                    │ claimed_by      │
                    │ idempotency_key │
                    └────────┬────────┘
                             │
                             │ Atomic claim:
                             │ FOR UPDATE SKIP LOCKED
                             ▼
                    ┌─────────────────┐
                    │ Worker Pool     │
                    │ n×worker:claim  │
                    └────────┬────────┘
                             │
            ┌────────────────┴────────────────┐
            │                                 │
            ▼                                 ▼
   ┌──────────────────┐             ┌────────────────┐
   │ Job Handlers     │             │ FailureHandler │
   │                  │             │                │
   │ PaymentHandler   │             │ Retry+jitter   │
   │ └─ payment       │             │ Dead-lettering │
   │    idempotency   │             │                │
   │                  │             └────────────────┘
   │ InventoryHandler │
   │ └─ atomic stock  │
   └─────────┬────────┘
             │
             ▼
     ┌─────────────────┐
     │ Redis           │
     │                 │
     │ Token bucket    │
     │ Stats cache     │
     └─────────────────┘


                    ┌─────────────────┐
                    │ Watchdog        │
                    │ watchdog:run    │
                    │ reclaim stuck   │
                    └────────┬────────┘
                             │
                             ▼
                        PostgreSQL
```

---

Start workers:

```bash
php artisan worker:claim worker-1
php artisan worker:claim worker-2
php artisan worker:claim worker-3
```

Start Worker Pool:
```bash
php artisan workerpool:start --workers=n
```

Start watchdog:

```bash
php artisan watchdog:run
```

---

## Highlights

### Atomic claiming

`DispatchClaimer` uses:

```sql
FOR UPDATE SKIP LOCKED
```

Workers claim jobs in a single atomic transaction, preventing duplicate processing.

**Verified:** Five concurrent workers processed jobs without duplicate claims.

---

### Failure handling

`FailureHandler` implements:

```text
2^attempts + rand(0,3)
```

- Exponential backoff
- Jitter to avoid retry storms
- Dead-lettering after max attempts

Failed jobs wait without blocking workers.

---

### Crash recovery

`WatchdogCommand` resets jobs stuck in `processing`.

**Verified:** Killing a worker with `kill -9` successfully re-queued the job.

**Tradeoff:** This provides **at-least-once delivery**, meaning handlers must be idempotent.

---

### Idempotency

**API level**

- Same key + same payload → `200 OK`
- Same key + different payload → `409 Conflict`
- New key → `201 Created`

**Server level idempotency**

`PaymentHandler` checks `payment_records` before charging.

---

### Inventory reservation

Uses:

```sql
UPDATE products
SET stock = stock - ?
WHERE id = ?
AND stock >= ?
```

The check and update happen atomically.

**Verified:** Concurrent reservations never pushed stock below zero.

---

### Redis rate limiting

Token bucket implemented in Redis Lua

Returns:

- `201 Created` while capacity exists
- `429 Too Many Requests` when exhausted

---

## Load testing

```bash
php artisan loadtest:start --workers=1
php artisan loadtest:start --workers=4
php artisan loadtest:start --workers=8
```

| Workers | Jobs | Duration | Throughput |
|---|---:|---:|---:|
| 1 | 1000 | — | — |
| 4 | 1000 | — | — |
| 8 | 1000 | — | — |

*Fill after final benchmark run.*

---

## Evidence

| Claim | Evidence |
|---|---|
| No duplicate claims | Five concurrent worker logs |
| Stock never negative | Database state after concurrent reservations |
| Crash recovery works | `kill -9` recovery logs |
| Payment handler is idempotent | One `payment_records` row after repeated execution |
| Invalid payloads rejected | `422` before queue insertion |
| Conflicting idempotency rejected | `409` response |
| Rate limiter works | Concurrent Postman tests |

---

## Native Laravel vs DIY

| Concern | Native Laravel | Dispatch Engine |
|---|---|---|
| Atomic claiming | ✅ Internal | ✅ Explicit |
| Retry handling | ✅ Built-in | ✅ FailureHandler |
| Dead-lettering | ✅ `failed_jobs` | ✅ `status = failed` |
| Unique jobs | ✅ `ShouldBeUnique` | ✅ Unique index |
| Progress tracking | ❌ | ❌ |
| Rate limiting | ❌ | ✅ Redis Lua |
| Claiming visibility | ❌ Internal | ✅ Readable implementation |

---

---

_Built as a deep dive into the problems every queue system must solve. Not a replacement for Laravel Horizon — a codebase designed to make every decision understandable._
