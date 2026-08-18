# List page size is 50 by default, 1000 maximum

`GET /object/get_all_records` returns at most 50 items unless the client asks for more, never more than 1000. Fifty keeps take-home responses small; 1000 is the production ceiling so one request cannot dump the snapshot. The spec’s unpaginated array is the collection walked via cursors, not a single unbounded body.
