# One POST accepts at most ten Keys

`POST /object` accepts one JSON object of Key → Value pairs. One pair is valid; several are valid up to ten. Ten is a config default, not a magic constant, so it can change later without a new endpoint. Unlimited maps would let one request write the world. Distinct instants are distinct POSTs, not extra Keys in the same body.
