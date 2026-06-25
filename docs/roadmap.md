# Roadmap

## Available Now

- HTTP client
- Token refresh
- Business APIs
- Customers
- Cards
- IBANs
- Inquiries
- IPG
- Refunds
- Settlements
- Queued settlements
- Batch settlements
- Avand/Cash-in
- Testing fakes

## Planned

- Direct debit APIs
- Ravand, card issuing, and banking APIs
- Optional encrypted database token store
- More typed response DTOs where useful

## Not Planned By Default

- Application-specific models
- Payment tables
- Routes or controllers
- UI components
- Business workflows
- Accounting or voucher logic

The package should remain SDK-first and app-agnostic. Application models, migrations, routes, controllers, workflows, and persistence belong in consuming Laravel applications.
