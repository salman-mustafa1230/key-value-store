# One write request, one Timestamp

Every Version created by a single POST shares the same server Timestamp. Stamping Keys as the loop persists them would pretend they happened at different times when they committed together. The spec’s examples are one Key per POST at different clock times; that remains true if the client sends separate requests. A batch is one instant with several Keys, not several instants.
