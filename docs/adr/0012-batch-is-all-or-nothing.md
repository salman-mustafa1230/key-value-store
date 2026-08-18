# A batch POST is all or nothing

One `POST /object` is one transaction: every pair becomes a Version, or none do. Partial success would invent a response shape the spec does not have and would make as-of reads during a batch a coin flip. Transient persistence failures retry the whole transaction with backoff; validation failures do not retry. After retries fail, the client gets an error and nothing is stored. Partial success — return the failed Keys so the client can fix and resubmit them — is a v2 note, not this submission.
