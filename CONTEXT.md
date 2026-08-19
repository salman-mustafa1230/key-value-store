# Versioned Key Store

A version-controlled key-value store. Clients write Values against Keys; every write appends an immutable Version; readers ask for the latest Value, the Value as of a Timestamp, or the current snapshot of every Key.

## Language

**Key**:
The identity of a stored item. A Key is 1–64 characters, `^[A-Za-z0-9][A-Za-z0-9_-]*$`, case-sensitive. `get_all_records` is not a legal Key; that name is the list path. A Key is required on every write. Writing an existing Key appends a Version; it does not replace the Key.
_Avoid_: Object, record, name, id, field

**Value**:
The payload of a Version. Any JSON value — object, array, string, number, boolean, null, or empty string — whose nesting depth is at most two (arrays count as a level). On write, encoded size is at most 8 KiB and each object or array has at most 100 members. Null and empty string are stored Values, not absence of a Key.
_Avoid_: Blob, object, data, body, document

**Version**:
An immutable fact that a Key had a Value at a Timestamp. The history of a Key is its Versions in Timestamp order.
_Avoid_: Record, revision, row, update, snapshot

**Timestamp**:
A UNIX UTC time the server assigns once per write request and shares across every Version created by that request. Stored with sub-second precision; the public as-of query is whole UNIX seconds.
_Avoid_: Time, datetime, created_at, clock

**Current snapshot**:
The latest Version of every Key. This is what “currently stored” means.
_Avoid_: All records, full history, dump, database contents

**As-of read**:
The newest Version of a Key whose Timestamp is less than or equal to the end of the requested UNIX second.
_Avoid_: Exact match, point-in-time restore, checkout

**Archive**:
A future Version that would hide a Key from the current snapshot without erasing history. Not part of this submission; no archive or delete endpoint exists.
_Avoid_: Delete, tombstone, soft delete, compaction, hard delete
