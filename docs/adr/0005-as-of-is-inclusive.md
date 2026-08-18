# As-of reads are inclusive; bad timestamps are client errors

`GET /object/{key}?timestamp=T` returns the newest Version with Timestamp ≤ the end of UNIX second T. Missing `timestamp` means latest. A future T is a legal as-of (latest, if any Version exists). Non-integer, negative, or empty T is 400 `invalid_timestamp`, not a silent fallback to latest. Because Timestamps are stored with sub-second precision, two Versions in the same second are distinct; as-of that second returns the later one.
