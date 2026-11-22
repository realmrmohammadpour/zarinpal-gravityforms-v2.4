# Why the original Gravity Forms ZarinPal gateway failed (and what v2.4 changes)

> توضیح فنی رفع خطای درگاه قدیمی زرین‌پال — فارسی و انگلیسی

## Original project / منبع اصلی

- Maintainer/source: **[ZarinPal-Lab/GravityForms](https://github.com/ZarinPal-Lab/GravityForms)**
- Original plugin source version: **2.3.0**, declared in `index.php` and `zarinpal.php`.
- Last code commit recorded on the original repository's `master` branch: **20 June 2018**.
- Historic source: [`zarinpal.php`, lines 1341–1350](https://github.com/ZarinPal-Lab/GravityForms/blob/master/zarinpal.php#L1341-L1350).

We appreciate the original project's work in supporting Iranian Gravity Forms payments. This independent compatibility rewrite is **not official**, not a fork that carries the original implementation forward unchanged, and is **not affiliated with ZarinPal**. No original PHP source files are bundled in this repository. Attribution does not imply endorsement.

## The fatal error / علت خطا

On a modern PHP + Gravity Forms installation, opening the original payment feed settings screen could fail with:

```text
Fatal error: Uncaught Error: Undefined constant "RG_CURRENT_VIEW"
.../wp-content/plugins/GravityForms-master/zarinpal.php:1348
```

The old source uses:

```php
if ( RG_CURRENT_VIEW != 'entry' ) {
```

The `RG_CURRENT_VIEW` constant is no longer defined in current Gravity Forms versions. On PHP 8+, referencing an undefined constant is a fatal `Error`. The Gravity Forms documentation explicitly describes `RG_CURRENT_VIEW` as a legacy constant used before version 2.3 and suggests this instead:

```php
if ( rgget( 'view' ) !== 'entry' ) {
```

Source: [Gravity Forms developer documentation — RG_CURRENT_VIEW](https://docs.gravityforms.com/rg_current_view/).

**Note:** This one-line edit resolves the observed fatal error at line 1348, but does **not** modernize the gateway's payment processing.

## Why a rewrite instead of only one line? / چرا فقط یک خط کافی نبود؟

The original 2.3.0 add-on also uses legacy `RGForms`/`RGFormsModel` calls, an old SOAP/WSDL-based ZarinPal payment API, and manually hooks into confirmation and callback processing. Those are separate compatibility and maintenance risks. A successful render of the settings page does not prove that the Request/Verify/payment lifecycle will work safely.

In v2.4.0 the relevant design changes are:

1. Replace the old admin page/constant dependency with the **GFPaymentAddOn** feed/settings framework. The runtime no longer refers to `RG_CURRENT_VIEW`.
2. Use ZarinPal **REST API v4** instead of the old SOAP/WSDL endpoints.
3. Store payment amount, unit, merchant and Authority with the originating Gravity Forms Entry before bank redirect.
4. Verify server-side with the saved amount and require a valid Ref ID; do not trust `Status=OK` alone.
5. Make repeat callback handling idempotent and leave ambiguous/network-error outcomes marked for review.
6. Import existing 2.3.0 settings and feeds non-destructively; migrated feeds remain inactive until reviewed.

**Important distinction:** these are implementation design and isolated-test findings. v2.4.0 has **not** been validated with a live merchant account or an end-to-end payment in production.

## Compatibility limits / محدودیت‌ها

This is not a feature-complete drop-in replacement for every use of 2.3.0. The initial rewrite does not implement subscriptions/recurring payments, legacy custom HANNANStd hooks, legacy reporting graphs, refunds/reverse, or automatic recovery of pre-upgrade pending SOAP callbacks. **Back up the old database, settle pending payments, and test on staging.**

Documentation: [GFPaymentAddOn](https://docs.gravityforms.com/gfpaymentaddon/), [ZarinPal payment gateway API](https://www.zarinpal.com/docs/paymentGateway/).