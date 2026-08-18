# Append-only Versions per Key

A Key is identity, not a mutable row. Every write — first insert or later “update” — appends an immutable Version `{Key, Value, Timestamp}`. In-place overwrite would make as-of reads a lie; storing history as an optional audit table would let the live row and the history diverge. Compaction and Archive can drop or hide Versions later without changing this rule.
