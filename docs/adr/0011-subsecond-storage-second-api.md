# Store Timestamps with sub-second precision; expose UNIX seconds

The public API uses UNIX seconds, as the spec requires. Internally a Version’s Timestamp has sub-second precision so two writes to the same Key in one second are two Versions, not a collision. A lock only orders writers; it does not invent a new UNIX second. As-of `T` means the latest Version with Timestamp ≤ the end of second T. Rejecting the second write (one Version per Key per second) would drop legal history.
