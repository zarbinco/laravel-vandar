# Usage Guide

This page moved to the bilingual documentation set.

[English Document](en/usage.md) | [Persian Document](fa/usage.md)

Compatibility safety note: the full usage guide still includes the Safe callback controller skeleton. It keeps the SDK boundary visible: verify before marking anything paid, compare amount, factorNumber/order id, token, and transaction id, and remember that Duplicate callbacks must be handled idempotently. The DB transaction belongs to your application.
