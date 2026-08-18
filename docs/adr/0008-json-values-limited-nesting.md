# Values are JSON, including null, with max depth two

A Value is any JSON value, including null and empty string. Those are stored data, not “no Key”. Nesting depth is at most two; arrays count as a level. `{ "a": { "b": 1 } }` is legal; `{ "a": { "b": { "c": 1 } } }` and `{ "a": [1, { "b": 1 }] }` are not. Missing Key and as-of-before-first-Version are not-found, not `null`, or a stored null Value would be indistinguishable from absence.
