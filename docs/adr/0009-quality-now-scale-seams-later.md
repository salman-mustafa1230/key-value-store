# Quality now, scale seams later

This submission is one region, one database, one tenant: structured errors, validation, tests, CI on master, deploy. It is not a cluster, a queue, or an auth product. The data model is already the scale path — append-only Versions, access by Key, cursor-listed snapshot — so sharding by Key, a snapshot read model, and Archive can be added without changing the language.
