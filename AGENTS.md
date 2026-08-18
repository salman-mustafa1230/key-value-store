# Versioned Key Store — agent instructions

Laravel HTTP API: append-only Versions per Key, PostgreSQL, as-of reads, cursor-listed current snapshot.

**Language** is in [`CONTEXT.md`](CONTEXT.md). Use those terms (Key, Value, Version, Timestamp, current snapshot, as-of). Do not invent Object/record/row as domain words.

**Decisions** are in [`docs/adr/`](docs/adr/). If a PR silently reverses an ADR, call it out.

## When reviewing a pull request

Work these steps in order. Done when every changed line has been checked against the rules below and every finding is a concrete file+issue, or you have confirmed there are none.

1. **Read the diff against the ADRs.** A write that overwrites a Version, a list that returns full history, a client-supplied write Timestamp, or a delete/Archive endpoint is a regression unless the PR adds a new ADR.
2. **Check the HTTP contract.** Public API is under `/api/v1`. `POST /api/v1/object` is 1–10 pairs, all-or-nothing, 201 with `{ data: [{ key, value, timestamp }] }`. `GET /api/v1/object/{key}` returns the raw Value. Missing Key / as-of-before-first is 404, never JSON `null`. `GET /api/v1/object/get_all_records` is `{ data: [{ key, value, timestamp }], next_cursor }`. Errors are `{ error: { code, message } }`. New features get a file in `routes/api/v1/`, not `web.php`.
3. **Check invariants.** `get_all_records` stays a reserved Key. Value nesting stays ≤ 2 (arrays count). Server stamps time once per POST. As-of is inclusive to the end of the UNIX second. Null Value ≠ missing Key.
4. **Check tests.** Every contract change has a PHPUnit case. Domain rules belong in `tests/Unit`; HTTP behaviour in `tests/Feature`.
5. **Security.** No concatenated SQL. No secrets in the diff. No new endpoint without validation. Flag path-traversal Keys and unbounded payloads.

Comment only on defects and ADR violations. Skip style nits Pint already covers.

## When changing code

Match `app/Domain`, `app/Application`, `app/Http/Controllers/Api/V1`, `app/Infrastructure`. New HTTP features go in `routes/api/v1/<feature>.php`. Do not put persistence or HTTP types in the domain. Update `CONTEXT.md` only when a term changes; add an ADR only when the choice is hard to reverse, surprising, and a real trade-off.
