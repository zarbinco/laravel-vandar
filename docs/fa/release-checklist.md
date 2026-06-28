# چک‌لیست Release

[فارسی](release-checklist.md) | [English](../en/release-checklist.md)

قبل از tag کردن release:

- `composer ci` را اجرا کنید.
- `composer validate --strict` را اجرا کنید.
- `composer format:test` را اجرا کنید.
- `composer analyse` را اجرا کنید.
- `composer test` را اجرا کنید.
- `composer release:audit` را اجرا کنید.
- مطمئن شوید `composer.lock` داخل release archive نیست.
- مطمئن شوید `vendor/` داخل release archive نیست.
- مطمئن شوید `REVIEW_NOTES.md` track یا منتشر نمی‌شود.
- مطمئن شوید credential واقعی در repo نیست.
- وضعیت [endpoint support](endpoint-support.md) را مرور کنید.
- `CHANGELOG.md` را به‌روز نگه دارید.
- نصب از Packagist و Laravel package discovery را بررسی کنید.

برای tag:

```bash
git status --short
composer ci
php scripts/audit-release.php
git tag -a vX.Y.Z -m "Release vX.Y.Z"
git show --stat vX.Y.Z
```

push و انتشار Packagist باید طبق workflow نگهدارنده انجام شود.

بعد از release، بهتر است در یک Laravel app تازه نصب را امتحان کنید، config را publish کنید، و یک تست ساده با `Vandar::fake()` بزنید.
