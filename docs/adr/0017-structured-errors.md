# Errors are a stable JSON object with a code

Error bodies are `{ "error": { "code": "...", "message": "..." } }`. Validation is 400, no Version for that read is 404, failure after transaction retries is 500. No HTML, no stack traces. Tests and CI need a machine-readable `code`; a message-only body is not enough to assert on.
