# انتشار در GitHub / Publishing

این بسته شامل سورس تمیز، تست‌ها، مستندات علت خطا و فایل ZIP قابل نصب است. انتشار روی GitHub به مجوز نوشتن در حساب خودتان نیاز دارد.

## با GitHub CLI روی Windows (PowerShell)

1. Git و [GitHub CLI](https://cli.github.com/) را نصب کنید.
2. فایل `zarinpal-gravityforms-2.4-github-source.zip` را Extract کنید.
3. در فولدر `zarinpal-gravityforms-2.4` ترمینال PowerShell باز کنید.
4. `gh auth login` را اجرا و وارد حساب خودتان شوید.
5. `./scripts/publish-github.ps1` را اجرا کنید. در صورت نیاز، اجرای Scriptها را طبق سیاست امنیتی ویندوز خود مجاز کنید.

اسکریپت مخزن عمومی `zarinpal-gravityforms-v2.4` ایجاد می‌کند و `v2.4.0` را به‌شکل **pre-release** با فایل `dist/ZarinPal-GravityForms-2.4.0.zip` منتشر می‌کند. اگر مخزن از قبل وجود داشته باشد، برای جلوگیری از overwrite متوقف می‌شود.

در صورت نیاز به اسم متفاوت، `./scripts/publish-github.ps1 -RepositoryName "my-zarinpal-gravityforms"` را اجرا کنید.

## بدون GitHub CLI

1. در GitHub یک مخزن جدید **Public** با نام پیشنهادی `zarinpal-gravityforms-v2.4` بسازید.
2. فایل‌ها و پوشه‌های داخل فولدر `zarinpal-gravityforms-2.4` را در ریشهٔ مخزن Upload کنید؛ **خود فولدر بالادستی را تودرتو آپلود نکنید**.
3. در بخش Releases با گزینه Draft a new release، تگ `v2.4.0` و متن فایل `RELEASE_NOTES_v2.4.0.md` را قرار دهید.
4. فایل `dist/ZarinPal-GravityForms-2.4.0.zip` را به‌عنوان asset اضافه کنید و **Set as pre-release** را انتخاب کنید.

قبل از نصب در سایت مالی زنده، README و SECURITY.md را مطالعه کنید. این نسخه فقط در تست‌های شبیه‌سازی‌شده تأیید شده است.