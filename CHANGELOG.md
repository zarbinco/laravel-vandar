# Changelog

All notable changes to `zarbinco/laravel-vandar` will be documented in this file.

## Unreleased

- Added InquiryResource for Vandar inquiry services.
- Added named methods for KYC, Shahkar, NID, NID image, Fida, postal code, company information, company signature, national-code/IBAN, mobile/card, IBAN, card, and card-to-IBAN inquiries.
- Hardened redaction keys for inquiry payloads and responses.
- Added inquiry resource tests.
- Added CardResource for customer card APIs.
- Added IbanResource for customer IBAN APIs.
- Added customer-scoped card/IBAN inquiry helpers.
- Added customer-scoped card-to-IBAN helper.
- Added tests for card and IBAN resources.
- Hardened redaction keys for card/account data.
- Added BusinessResource.
- Added CustomerResource.
- Added CustomerFieldResource.
- Added BusinessResolver and VandarPath helpers.
- Added tests for business/customer resources.
- Hardened URL sanitization for package logging.
- Hardened exception context redaction.
- Added tests for sensitive URL, log, and exception redaction.
- Added generic HTTP client foundation.
- Added VandarResponse DTO.
- Added token store abstraction.
- Added config/cache token stores.
- Added token manager and refresh-token command.
- Added RawResource for advanced generic requests.
- Added exception mapping and related tests.
- Renamed package owner, Composer package name, and PHP namespace from mrezdev/Mrezdev to zarbinco/Zarbinco.
- Added the Phase 1 Laravel package foundation.
- Added package service provider, facade, root SDK object, configuration file, and about command.
- Added recursive sensitive data redaction support.
- Added offline PHPUnit and Orchestra Testbench coverage for package registration, configuration, command output, and redaction behavior.
