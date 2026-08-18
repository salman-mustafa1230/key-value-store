# Server assigns write Timestamps

The server stamps a Version when it accepts a write. Clients do not send a write time. Client clocks are untrusted and would make “value at time T” depend on the caller. Query `timestamp` remains client-supplied: that is a lookup, not a write.
