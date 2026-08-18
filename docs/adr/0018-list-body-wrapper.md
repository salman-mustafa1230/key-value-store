# List body is an object with data and next_cursor

`GET /object/get_all_records` returns `{ "data": [ { "key", "value", "timestamp" }, ... ], "next_cursor": null | "<opaque>" }`, not a bare JSON array. The spec asked for an array of current records; pagination needs a place to put the next page token. A wrapper keeps that token in JSON beside our structured errors. Each item is the latest Version, so `timestamp` is the same UNIX seconds as POST — without it the list cannot feed as-of reads. `next_cursor` is null on the last page. The README notes the wrapper.
