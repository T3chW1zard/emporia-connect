# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

First release.

### Features
- Client for the Emporia Vue API: customer details, devices (including sub-devices), location properties, device status, channels, channel types, instant usage, chart usage, outlets, EV chargers, vehicles and the maintenance check.
- AWS Cognito login with SRP (`USER_SRP_AUTH`, as used by the Emporia app) or the plain password flow.
- Automatic token refresh with the refresh token, and a transparent retry after a `401`.
- Token caching in any PSR-16 or PSR-6 cache, in a JSON token file, or in a custom `TokenStoreContract`.
- Starting from existing tokens (`EmporiaConnect::fromTokens()`).
- Exponential back-off retries for `5xx` responses.
- `usage()->devices()` for several devices at once, with nested devices and a retry when data is missing.
- `usage()->chart()` / `chartForChannel()` with `points()`, `watts()` and `total()` helpers.
- Typed, `final readonly`, `JsonSerializable` response classes for every endpoint.
- Typed exceptions: `AuthenticationException`, `ApiException` (with status code and body) and `TransportException`.
- `ClientBuilder` with PSR-18 client, timeouts, retry policy, auth flow, clock and base URI options.
- Laravel service provider (auto-discovered), `config/emporia.php` and `Emporia` facade with `Emporia::fake()`.
- Symfony `EmporiaConnectBundle` with a configuration tree.
- `FakeClient` / `FakeTransporter` with realistic fixtures and request recording.
