# ZarinPal for Gravity Forms 2.4.0 — Preview release

**Independent, unofficial rewrite** based on the compatibility needs discovered in [ZarinPal-Lab/GravityForms](https://github.com/ZarinPal-Lab/GravityForms) v2.3.0. The original project last received a source commit on **20 June 2018**. Credit for the original plugin belongs to its authors and maintainers; v2.4.0 is not an official ZarinPal release.

## Why this version exists

In the legacy `zarinpal.php` at line **1348**, `RG_CURRENT_VIEW` is accessed directly. Modern Gravity Forms does not define the historical constant, so PHP 8+ raises:

```text
Fatal error: Uncaught Error: Undefined constant "RG_CURRENT_VIEW"
```

Gravity Forms documents `rgget( 'view' )` as the replacement for that removed constant: https://docs.gravityforms.com/rg_current_view/. A one-line fix can restore the **settings screen**, but the legacy code also uses outdated Gravity Forms classes and SOAP payment endpoints.

## Changes

- Uses the `GFPaymentAddOn` framework instead of obsolete `RG_CURRENT_VIEW`/`RGFormsModel` flows.
- Implements ZarinPal payment requests and verification through REST API v4.
- Supports IRR/IRT conversion, Merchant UUID and Sandbox.
- Binds Authority and amount to the original form Entry; verifies payment server-side.
- Guards against replayed callbacks and marks uncertain payment results for review.
- Migrates old settings and feeds non-destructively (migrated feeds initially disabled).
- Includes signed receipt links, user-facing result pages, cache-bypass guidance, and mocked regression tests.

## Known limitations

- **Not yet tested against a live merchant or a complete WordPress + Gravity Forms deployment.** Use staging and Sandbox before production.
- Supports one-time payments only. No subscriptions, recurring transactions, reverse/refunds, legacy custom hooks, or classic reporting graphs.
- Pending payment requests created with v2.3.0 are **not** automatically completed by v2.4.0.
- Callback and receipt query parameters must be excluded from page caches/CDNs.

Install the attached WordPress plugin ZIP. **Do not use GitHub's generic “Source code (zip)” archive as a substitute for the release-specific installer package** if you rely on replacing the legacy `GravityForms-master` folder.