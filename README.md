# ZarinPal for Gravity Forms — independent project

> [!CAUTION]
> **PRODUCTION RELEASE HOLD — 2026-11-24**
>
> The 2.4.x previews are not approved for new live payment deployments. Follow-up reviews identified unresolved payment correctness, recovery, integration and privacy requirements. There is no approved stable release at present.
>
> **Existing installations:** arrange a controlled pause of new payments while preserving existing callbacks and all financial records. Back up privately and reconcile against the merchant's authoritative records before retry, migration, downgrade or uninstall. Do not mark an entry Paid based on a browser return alone.
>
> **هشدار:** نسخه‌های 2.4.x برای استفاده عملیاتی تأیید نشده‌اند. نصب جدید انجام ندهید. در صورت نصب قبلی، بدون تطبیق تراکنش‌های جاری و بک‌آپ، افزونه را حذف یا جایگزین نکنید.

This is an independent, unofficial compatibility rewrite of [ZarinPal-Lab/GravityForms](https://github.com/ZarinPal-Lab/GravityForms). The historic 2.4.0 preview addressed the removed Gravity Forms constant and replaced legacy SOAP communication; it is not a certified replacement.

## Status and development

- [Release hold and existing-installation safety instructions](docs/RELEASE_HOLD.md)
- [Release acceptance gates](docs/V2_5_RELEASE_GATES.md)
- [Production preservation tracker](https://github.com/realmrmohammadpour/zarinpal-gravityforms-v2.4/issues/1)
- [All v2.5 workstreams](https://github.com/realmrmohammadpour/zarinpal-gravityforms-v2.4/issues)
- [Historical 2.4.2 source baseline](https://github.com/realmrmohammadpour/zarinpal-gravityforms-v2.4/tree/baseline/v2.4.2) — **do not deploy**
- [v2.5 development branch](https://github.com/realmrmohammadpour/zarinpal-gravityforms-v2.4/tree/develop/v2.5) — development only
- [Security reporting policy](SECURITY.md)

The default branch retains historical source; current distribution and automated publishing files have been removed from this branch. Historical GitHub release assets may still exist; their presence is not a recommendation to use them. All gates remain open; no new release until reviewed and explicitly approved. Previous mock tests do not constitute a live transaction certification.

Do not post customer records, merchant credentials, payment tokens or vulnerability reproductions publicly. No live customer incident is asserted by this notice.
