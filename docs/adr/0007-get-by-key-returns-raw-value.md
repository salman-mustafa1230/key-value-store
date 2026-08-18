# GET-by-key returns the Value; the list wraps a page

`GET /object/{key}` (latest or as-of) responds with the raw JSON Value, matching the spec’s “Response: value1 object”. The current-snapshot list cannot do that — it must include the Key and a cursor — so the body is `{ "data": [ { "key", "value" } ], "next_cursor" }`. Envelope-everywhere would be cleaner and would fail the spec examples for GET-by-key.
