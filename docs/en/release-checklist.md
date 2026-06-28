# Release Checklist

[English](release-checklist.md) | [فارسی](../fa/release-checklist.md)

Use this checklist before tagging a package release.

## Before tagging

- Run `composer ci`
- Run `composer validate --strict`
- Run `composer format:test`
- Run `composer analyse`
- Run `composer test`
- Run `composer release:audit`
- Confirm no `composer.lock` is included in the release archive
- Confirm no `vendor/` directory is included
- Confirm `REVIEW_NOTES.md` is not tracked or included in the release archive
- Confirm no real credentials are present
- Confirm `docs/endpoint-support.md` is current
- Confirm `CHANGELOG.md` has release notes
- Confirm README installation works
- Confirm Laravel package discovery is correct
- Confirm Packagist metadata is correct

## Tagging

- Update `CHANGELOG.md`
- Create a git tag
- Push the tag
- Verify the Packagist update

## After release

- Install into a fresh Laravel app
- Publish config with `php artisan vendor:publish --tag=vandar-config`
- Run a fake request test with `Vandar::fake()`
