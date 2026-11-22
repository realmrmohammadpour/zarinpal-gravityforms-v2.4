# Changelog

## 2.4.0 — 2026-11-22

- Independent WordPress/Gravity Forms gateway rewrite; replaces SOAP/WSDL with ZarinPal REST API v4.
- Uses the modern `GFPaymentAddOn` feed and payment infrastructure; targets Gravity Forms 3.2.3.1.
- Removes reliance on removed Gravity Forms constant `RG_CURRENT_VIEW` and old `RGFormsModel`/`RGForms` usage.
- Supports merchant UUID, ZarinPal Sandbox and separate source/API currencies (IRT/IRR).
- Ties Authority to the originating GF Entry and a signed, opaque callback token.
- Saves bank amount and currency before redirect, performs server-side Verify with original amount.
- Requires a matching Ref ID for repeat Verify result 101; never auto-fulfills unknown transactions.
- Implements atomic callback lock and idempotent completion/delayed-feed dispatch.
- Captures network uncertainty as `review`, not a false final payment result.
- Adds protected payment receipt page and retry review link with cache-bypass headers.
- Imports legacy 2.3 settings and feeds non-destructively into native GF feeds. Migrated feeds are initially inactive pending review.
- Includes installation, cache exclusion, migration and financial testing checklist.

**Validation:** PHP 8.4 syntax and isolated mocked integration tests. No production or live acquiring test included.