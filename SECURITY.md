# Current release status — HOLD

Production use of the 2.4.x previews is not approved. Read [the safety notice](docs/RELEASE_HOLD.md). Keep vulnerability reproductions and sensitive payment information private; do not disclose them through public issues. No supported stable version is currently available.

# Security policy / سیاست گزارش آسیب‌پذیری

This project processes payment state. It is an **independent, pre-production-validated rewrite**; no claim of external penetration testing, formal code audit or successful production transaction is made.

- Do not expose merchant account data, customer records, live callback tokens, full HTTP logs, or production credentials in GitHub Issues.
- If the repository enables GitHub private vulnerability reporting, use that channel. Otherwise contact the repository maintainer privately before publishing exploit details.
- Test with the ZarinPal Sandbox and a staging site first. Keep the old plugin and database backups until pending payments are settled.
- Exclude callback and receipt query parameters from all page caches and CDNs.
- Review any `review`/unknown transaction directly against trusted ZarinPal data before manually updating an Entry to Paid.
- No security updates or support response times are guaranteed.

توجه: برای گزارش مشکلات امنیتی، توکن بازگشت، اطلاعات بانکی یا مشخصات مشتری را در Issue عمومی ارسال نکنید.