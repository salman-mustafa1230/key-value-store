# List returns the current snapshot

`GET /object/get_all_records` returns the latest Version of each Key, not every Version ever written. The spec’s “currently stored” is live state; time travel is already `GET /object/{key}?timestamp=`. A full-history dump would explode with Keys × Versions and is an export, not a list.
