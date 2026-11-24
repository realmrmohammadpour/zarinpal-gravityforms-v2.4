# 2.4.x release hold

**Status: HOLD — not approved for live payments.** Effective 2026-11-24.

Affected scope: 2.4.0 public preview and subsequent 2.4.1/2.4.2 experimental packages. These are not official ZarinPal releases. Three follow-up reviews of 2.4.2 found unresolved correctness and security-hardening requirements. Prior mock test results do not establish production safety. No live incident or data exposure is asserted by this notice.

## Existing installations
- Do not start new live deployments. If already processing payments, arrange a controlled pause of new payment initiation; preserve the callback endpoint and records of in-flight attempts.
- Back up the full database, current plugin directory and relevant configuration privately. Verify restore on isolated staging with outbound payment and operational messaging disabled.
- Reconcile local entries and payment records against trusted ZarinPal merchant records, checking amount, currency, authority, reference, financial status, environment and fulfillment separately.
- Treat unknown or conflicting outcomes as requiring manual reconciliation. Do not automatically retry, re-charge, replay hooks, overwrite historical evidence, or mark Paid based solely on a browser return.
- Preserve the old plugin until pending attempts are resolved and a tested migration/rollback plan exists.
- Check external field mappings; only necessary payment metadata may be sent. Disable mappings of sensitive fields; investigate potential exposure privately if there is evidence.
- Do not put merchant identifiers, callback URLs/tokens, customer records, database dumps, reproduction payloads or exploit details in public issues.

## Release policy
No new installable release or stable tag until the gates in [V2_5_RELEASE_GATES.md](V2_5_RELEASE_GATES.md) pass. Baselines are historical source for development, not a recommendation to install. The old GitHub release is not a safe fallback. Existing release assets and tags must not be silently overwritten.

Report security issues privately using the process in [SECURITY.md](../SECURITY.md).
