# Public API is versioned under /api/v1

All key-store HTTP routes live under `/api/v1`. The spec examples used `/object`; the prefix is a version seam so `/api/v2` can land later without breaking v1 clients. Browser UI (`/swagger`) and health (`/up`) stay unversioned. New features add a file under `routes/api/v1/`, not a new line in `web.php`.
