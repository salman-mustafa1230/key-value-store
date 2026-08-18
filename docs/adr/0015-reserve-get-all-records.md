# `get_all_records` is a reserved Key

The spec lists at `GET /object/get_all_records` and reads a Key at `GET /object/{key}`. If `get_all_records` were a legal Key, those routes would collide. Writes of that name are rejected. We considered moving the list to `GET /object?all=1` (no reserved Key) and rejected it: that drops the path the document named. An alias query can be a later extra; it is not this submission and must not replace `/object/get_all_records`.
