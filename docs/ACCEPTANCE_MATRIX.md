# Acceptance matrix — open, not passed

The three audit sets are retained privately as synthetic reproduction fixtures. Public criteria below intentionally omit reproduction payloads. All 46 observation IDs are accounted for; they are overlapping behavioral checks, not 46 independent vulnerabilities. Convert each into a positive acceptance test on the new architecture and retain the old reproducer for comparison. A missing method, exception, skipped test, or changed log message is not acceptance.

| Workstream | Audit references | Acceptance invariant |
|---|---|---|
| Attempt and financial evidence | A01, A04, A09, A10, F01, F02, F04, R07, R08, R09, R10, GF02, GF04, ZP01, ZP02, ZP03 | Preserve evidence, enforce monetary/currency contract and permitted state transitions; handle invalid upstream response without false success. |
| Recovery and concurrency | A08, A12, ZP05 | Owner-aware locking, bounded retries, reconcile unknown results with authoritative evidence. |
| Framework and fulfillment | A02, A03, A05, A06, A07, A11, F03, R01, R02, R11, R18 | One canonical notification path; required payment and dependent operations gated; preserve exact framework contracts. |
| Receipts and confirmation | R03, R04, R05, R06 | Read-only, truthful, authorized receipt, safe destinations and defined historical confirmation behavior. |
| Migration and configuration | R13, R14, R15, R16, R17, GF03 | Versioned non-destructive migration with preview, validation and recoverability; preserve explicit settings and report unsupported semantics. |
| API boundary and privacy | R12, GF01, WP01, WP02, WP03, ZP04 | Typed response validation, bounded resources, privacy-safe field mappings and diagnostics, robust token lifecycle. |

Additional static-review gates: independent financial retention; multisite/staging isolation; plugin lifecycle and capabilities; least-privilege admin actions; privacy exports/retention; provider fee and status semantics; exact WP/GF/Persian GF/SMS contracts; browser/cache and analytics behavior; CI/security review. See the release gates. Actual WP/PHP/DB integrations are mandatory; local mocks cannot sign off these gates.
