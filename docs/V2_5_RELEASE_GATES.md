# v2.5 release gates

Status: all gates OPEN; development only. No production release authorization.

| Gate | Required evidence |
|---|---|
| G0 Preservation | Verified 2.4.2 source baseline; private backup/restore; merchant reconciliation; inventory in-flight payments |
| G1 Financial core | Immutable attempt and transaction evidence; explicit transition rules; correct integer amounts and currencies; verified durable writes; live/sandbox isolation |
| G2 Recovery | Bounded retries; owner-aware locking and database uniqueness; independent inquiry/reconciliation; no blind re-charge/replay |
| G3 Framework | Actual WP + GF integration, Persian GF and SMS compatibility; correct events, conditional logic, mandatory-payment policy, deferred fulfillment |
| G4 Confirmation | Read-only receipt; native page/message/redirect behavior; safe destinations; no duplicate financial operations or misleading outcomes |
| G5 Migration | Dry run, journal, idempotent batches, preservation of in-flight and historical data, rollback rehearsal, role and privacy controls |
| G6 Quality | All original regression tests plus acceptance inversions of audit scenarios, strict API response tests, actual database concurrency and fault tests, PHP/WP/GF compatibility matrix |
| G7 Security | Independent security review of exact release commit; resolve release-blocking findings; no secrets in logs, packages or public issues |
| G8 Deployment | Real staging integration, controlled test transaction, confirmation, notification and recovery verification; maintainer signs off before any stable tag |

No gate may be marked complete on the basis of a mock-only pass or a plan. Keep detailed vulnerability reproductions and live records private. A public issue may describe required outcomes without exploit instructions. No automatic publish from development branches.
