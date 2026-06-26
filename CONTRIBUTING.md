# Contributing

Thanks for helping improve Laravel Vandar SDK.

## Local Setup

```bash
composer install
composer test
composer format:test
```

Use `vendor/bin/pint` to apply formatting fixes.

## Quality Checks

Before opening a pull request, run:

```bash
composer format:test
composer analyse
composer test
composer audit:release
```

## Pull Requests

- Keep the package SDK-first and app-agnostic.
- Add or update tests for behavior changes.
- Do not add routes, controllers, migrations, views, models, scheduled jobs, or app-specific workflows.
- Do not use real Vandar credentials, API keys, cards, IBANs, national codes, mobile numbers, callback URLs, or private customer/payment data in tests, docs, issues, or pull requests.
- Keep tests offline with Laravel HTTP fakes or `Vandar::fake()`.
- Update docs when public APIs or security guidance changes.

## Release Checks

Before opening a release-oriented pull request, run:

```bash
composer validate --strict
composer dump-autoload
composer analyse
composer test
composer format:test
composer audit:release
```
