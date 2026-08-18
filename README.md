# Versioned key-value store

HTTP API for a version-controlled key-value store (Secretlab backend exercise). PHP/Laravel, PostgreSQL.

Domain language lives in [`CONTEXT.md`](CONTEXT.md). Decisions live in [`docs/adr/`](docs/adr/).

## Why the list is paginated

The spec asks to display *all values currently stored*. That is the **current snapshot** (latest Version per Key), not full history.

Returning every Key in one JSON array is correct for a tiny dataset and will not survive the scale axis this service is designed for (many Keys). `GET /api/v1/object/get_all_records` therefore returns a page:

```json
{
  "data": [{ "key": "mykey", "value": "value1", "timestamp": 1440568800 }],
  "next_cursor": null
}
```

Walk `next_cursor` until it is null. Default page size is 50; maximum is 1000. History is `GET /api/v1/object/{key}?timestamp=`.

`GET /object?all=1` was considered so `get_all_records` would not need to be a reserved Key. It is **not** implemented: the list path is `/api/v1/object/get_all_records`.

## API

| Method | Path | Result |
| --- | --- | --- |
| `POST` | `/api/v1/object` | 201 `{ "data": [{ "key", "value", "timestamp" }] }` — 1–10 Key/Value pairs, one Timestamp for the request, all-or-nothing |
| `GET` | `/api/v1/object/{key}` | Raw JSON Value (latest) |
| `GET` | `/api/v1/object/{key}?timestamp=UNIX` | Raw JSON Value as of that UNIX second (inclusive) |
| `GET` | `/api/v1/object/get_all_records` | `{ "data": [{ "key", "value", "timestamp" }], "next_cursor" }` — current snapshot page |
| `GET` | `/swagger` | Swagger UI |

Errors: `{ "error": { "code", "message" } }` with 400 / 404 / 500.

Interactive docs: [http://localhost:8000/swagger](http://localhost:8000/swagger) (or `/swagger` on Railway). OpenAPI JSON is at `/docs`.

## Folder structure

This is one bounded context (**KeyStore**) split so a second feature does not land in the same files.

| Path | Role |
| --- | --- |
| `app/Domain/KeyStore` | Rules and language (Key, Value, Version). No HTTP, no SQL. |
| `app/Application/KeyStore` | Use cases: write, read, list. |
| `app/Http/Controllers/Api/V1` | HTTP adapters for **this** API version. |
| `app/Infrastructure/Persistence/KeyStore` | Postgres. Swap later without touching domain. |
| `routes/web.php` | Browser/meta only (`/`, not the API). |
| `routes/api/v1/*.php` | One file per feature. `objects.php` is the key store. Add `users.php` later; `v1.php` loads every file in that folder. |

A new feature: domain + application folders, a controller under `Api/V1`, a new file in `routes/api/v1/`. A breaking HTTP change: `routes/api/v2/` plus another prefix group in `bootstrap/app.php`. `web.php` stays small on purpose.

Keys: `^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$`. `get_all_records` is reserved. Values are JSON with max nesting depth 2. Null and empty string are stored Values, not “missing”. Missing Key is 404.

## Local development

PostgreSQL runs in Docker. The Laravel app can run on the host or in Compose.

```bash
cp .env.example .env
php artisan key:generate

docker compose up -d postgres
php artisan migrate
php artisan serve
```

Full stack (app + Postgres):

```bash
docker compose up --build
```

The app container expects `APP_KEY` in `.env`. Tests use database `keystore_test` (created by `docker/postgres/init.sql` on first volume init):

```bash
php artisan test
```

If `keystore_test` is missing (volume already existed):

```bash
docker compose exec postgres psql -U keystore -d keystore -c 'CREATE DATABASE keystore_test;'
```

## Environments

Two committed templates. Real `.env` / `.env.production` stay gitignored.

| File | Use |
| --- | --- |
| [`.env.example`](.env.example) | **Local.** `cp .env.example .env` then `php artisan key:generate`. Debug on, SSL `prefer`, Compose Postgres. |
| [`.env.production.example`](.env.production.example) | **Production.** Copy these names onto the Railway app service. Debug off, SSL `require`, logs to stderr, `DB_URL` from the Postgres plugin. |

Staging is not in this submission. PHPUnit uses `APP_ENV=testing` in `phpunit.xml` and database `keystore_test`.

## Railway (free instance)

Railway does **not** run `docker-compose.yml`. Compose is local Postgres only. Production Postgres is Railway’s managed plugin. The root `Dockerfile` is what Railway builds.

1. Create a Railway project and add a **PostgreSQL** service.
2. Deploy this repo as a service (GitHub or `railway up`). Railway will use `Dockerfile` (`railway.toml`).
3. Variables on the **app** service: copy [`.env.production.example`](.env.production.example). Required secrets:
   - `APP_KEY` — `php artisan key:generate --show`
   - `DB_URL=${{Postgres.DATABASE_URL}}` (reference the plugin; Laravel also reads `DATABASE_URL`)
   - `APP_URL` — the public Railway domain once you generate it
4. Generate a public domain. Health check: `GET /health` (Railway uses this path; timeout is 300s so migrate-on-boot can finish).
5. Migrations run on container start (`docker/start.sh`). The process listens on Railway’s `PORT`. Deploy logs print migrate output and whether `APP_KEY` / `DB_URL` are set (not the secret values). HTTP 500s also go to stderr.

Free-tier notes: this image uses `php artisan serve` so memory stays low. Cron/queue workers are not required for this API. If the instance sleeps, the first request after wake may run migrations again (`migrate` is idempotent).

## Postgres connections

PHP PDO does not share sockets the way PgBouncer does. `DB_POOL_SIZE=15` is the **app budget**: keep PHP workers (and later Octane clients) at or below 15, and set PgBouncer `default_pool_size` to 15. Do **not** set Postgres `max_connections` to 15 — Compose leaves the server at 100 so migrations and `psql` still fit.

Each session also gets production defaults from [`config/database.php`](config/database.php) and [`ConfigurePostgresSession`](app/Infrastructure/Persistence/ConfigurePostgresSession.php):

| Setting | Value | Why |
| --- | --- | --- |
| Pool budget | 15 | Cap concurrent app sessions |
| Persistent PDO | off | Dead sockets skip session GUCs |
| SSL | `prefer` local / `require` production | Encrypt in transit |
| `application_name` | `key-value-store` | `pg_stat_activity` |
| Time zone | UTC | Match stored Timestamps |
| Isolation | read committed | Default; no extra locking |
| Connect timeout | 5s | Fail fast on a dead host |
| `statement_timeout` | 15s | Kill runaway queries |
| `idle_in_transaction_session_timeout` | 30s | Do not hold locks forever |
| `lock_timeout` | 5s | Do not queue behind a stuck writer |
| Native prepares | on unless `DB_PGBOUNCER=true` | Transaction pooling cannot use them |

Put PgBouncer (transaction mode) in front when a single process is no longer enough. That is still a later scale step; the knobs above are the contract it must honour.

## CI/CD (GitHub → Railway)

Yes. GitHub Actions + Railway can run the whole pipeline. Pick **one** deploy trigger so you do not ship twice.

| Event | Workflow | What runs |
| --- | --- | --- |
| Pull request | [`.github/workflows/ci.yml`](.github/workflows/ci.yml) | PHPUnit, security scan, CodeQL, then Docker image **build** (no push). Docker waits for the three checks. |
| Push / merge to `main` or `master` | same | Same checks. Production is **not** updated. Deploy is not in this workflow. |
| GitHub Release (not a pre-release) | [`.github/workflows/release.yml`](.github/workflows/release.yml) | Re-runs those checks, then **deploys** that tag to Railway if secrets exist |

On every PR:

- **PHPUnit** — API tests against Postgres
- **Composer audit** — known-vulnerable PHP packages
- **Trivy** — HIGH/CRITICAL vulnerabilities and leaked secrets
- **CodeQL** — GitHub Actions workflow analysis (CodeQL does not support PHP)

A red security job fails the PR the same way a red test does. Dependabot (`.github/dependabot.yml`) opens weekly PRs for Composer, Actions, and Docker base-image updates.

**What is free vs paid**

| Tool | Cost | What it does |
| --- | --- | --- |
| Dependabot | **Free** on GitHub Free (public and private) | Alerts + PRs for vulnerable/outdated Composer, Actions, and Docker images |
| PHPUnit, Trivy, Composer audit | **Free** (GitHub Actions minutes; public repos are free) | Tests and security scan on every PR |
| CodeQL | **Free** on public repos; private repos need GitHub Advanced Security on some plans | Static analysis of GitHub Actions workflows (PHP is not a CodeQL language) |
| Copilot code review (`AGENTS.md`) | **Not free.** Copilot Free does **not** include PR auto-review. Needs Copilot Pro / Pro+ / Business | AI comments on the PR, using [`AGENTS.md`](AGENTS.md) |

[`AGENTS.md`](AGENTS.md) is in the repo so Copilot (or Cursor) follows this project’s review rules **if** you turn auto-review on. The file itself costs nothing. Automatic review on every PR is a Copilot paid feature: repo **Settings → Copilot → Code review → automatic reviews**, or request **Copilot** as a reviewer. Without that license, the free path is the Actions jobs above — they already run on every PR.

### Option A — Railway watches GitHub (deploys every merge)

1. In Railway: service → **Settings** → **Source** → connect this GitHub repo, branch `main`.
2. Enable **Wait for CI** so Railway stays in `WAITING` until this workflow is green, then builds the Dockerfile and deploys. A failed test **skips** the deploy.
3. Do **not** set `RAILWAY_TOKEN` in GitHub. This path ignores GitHub Releases and ships `main` as soon as CI is green.

### Option B — GitHub Release deploys ([`release.yml`](.github/workflows/release.yml))

Use this if you want production to move only when you publish a version. That workflow does not run on pull requests, so **Deploy to Railway** does not appear on PR checks.

1. In Railway: create a **project token** (Project settings → Tokens). Turn **off** GitHub autodeploy so merge to `main` does not ship.
2. GitHub repo → **Settings → Environments → Production** (create it if needed) → **Environment secrets**:
   - `RAILWAY_TOKEN` — that project token
   - `RAILWAY_SERVICE` — the app **service name** (not the Postgres plugin)
   
   Repository-level Actions secrets are **not** visible to this job. `release.yml` uses `environment: Production`.
3. Merge to `main` (CI only). Then create a GitHub Release from that commit — tag `v1.0.0`, not a pre-release. The Release workflow re-runs PHPUnit, security, CodeQL, and the image build, then checks out the tag and runs `railway up --ci --service …`.

```bash
git tag v1.0.0
git push origin v1.0.0
gh release create v1.0.0 --generate-notes
```

Or GitHub → **Releases** → **Draft a new release**. Pre-releases do not deploy.

Without those secrets, tests and the image build still run; deploy is skipped with a log line (so a dummy PR/merge stays green).

Dummy PR: open any small PR against `main` → you should see **PHPUnit**, **Security scan**, **CodeQL**, and **Build image**. There is no Deploy job on PRs. After merge those run again. **Deploy to Railway** is the Release workflow, only when you publish a Release.

## Scale later (not in this submission)

- Read replicas for GET latest / as-of.
- Shard **Versions** by Key.
- Snapshot table is already a read model seam; it can move to its own store.
- Archive Versions (soft-hide from the snapshot) without deleting history.
- v2: partial-success batch responses; optional list alias. New HTTP versions go under `/api/v2`.

## AI tools

This submission was built with **Cursor** (agent-assisted implementation) guided by the Secretlab spec, `CONTEXT.md`, and the ADRs. The design (domain language, API semantics, Postgres, pagination) was decided in a review session and recorded before code. I remain responsible for the behavior, tests, and trade-offs.
