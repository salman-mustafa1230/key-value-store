# Successful POST returns 201 with stored Versions

`POST /object` responds **201** `{ "data": [ { "key", "value", "timestamp": <unix seconds> } ] }` for every Version just written. The spec shows no success body; without the server Timestamp the client cannot make the as-of query the spec requires. An empty 201 forces an extra GET. Echoing the request object with 200 hides that each write created Versions, not an in-place overwrite. `timestamp` in this body is whole UNIX seconds, same as the public as-of parameter.
