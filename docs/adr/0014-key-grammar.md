# Keys are case-sensitive alphanumeric with optional _ and -

A Key matches `^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$`. The spec only says “string” and shows `mykey`; unbounded strings invite empty Keys and path-like values in `/object/{key}`. Underscore and dash are allowed so `user_id` and `my-key` work; the first character must be alphanumeric so `-hidden` cannot look like a flag. `MyKey` and `mykey` are different Keys. `get_all_records` is reserved (see ADR 0015).
