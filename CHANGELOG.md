# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

Complete rewrite covering the full Emporia API. This release contains breaking changes.

### Fixed
- API requests now send the Cognito id token in the `authtoken` header without a `Bearer ` prefix, as the Emporia API expects.
- Outlets, chargers and device status are parsed from the flat objects the API returns (previously `outletOn`/`chargerOn` always read `false`).
- Outlet and charger updates send the flat payload the API expects, including `loadGid`.
- Vehicles and channel types are read from the plain JSON lists the API returns (previously always empty).
- Vehicle status is read from the `settings` object (previously always `null`).
- Channel updates use `PUT devices/{deviceGid}/channels` with the full channel object.
- Devices include their own channels, and sub-devices are added to the device list.
- Location properties (`deviceName`, `displayName`, coordinates, household information) are parsed from `locationProperties`.
- Missing usage values stay `null` instead of turning into `0`.
- `ext-bcmath` is no longer required.

### Added
- Cognito SRP login (`USER_SRP_AUTH`, as used by the Emporia app), alongside the plain password flow.
- Token refresh with the refresh token, and transparent retry after a `401`.
- Token caching in any PSR-16 or PSR-6 cache, in a JSON token file, or in a custom `TokenStoreContract`.
- Starting from existing tokens (`EmporiaConnect::fromTokens()`).
- Exponential back-off retries for `5xx` responses.
- `usage()->devices()` for several devices at once, with nested devices and a retry when data is missing.
- `usage()->chart()` / `chartForChannel()` with `points()`, `watts()` and `total()` helpers.
- `devices()->find()`, `locationProperties()`, `populateLocationProperties()`, `withConnectionStatus()`.
- `channels()->all()`, `find()`, `update()` for a `DeviceChannelResponse`.
- `outlets()->turnOn()`/`turnOff()`, `chargers()->turnOn()`/`turnOff()`.
- `vehicles()` resource.
- `downForMaintenance()` check.
- New response classes: `LocationPropertiesResponse`, `LocationInformationResponse`, `DeviceConnectionResponse`, `UsageDeviceResponse`, `DeviceChannelUsageResponse`, `ChartUsageResponse`. All responses are `final readonly` and `JsonSerializable`.
- Typed exceptions: `ApiException` (with status code and body) and `TransportException`.
- `ClientBuilder` with PSR-18 client, timeouts, retry policy, auth flow, clock and base URI options.
- Laravel service provider (auto-discovered), `config/emporia.php` and `Emporia` facade with `Emporia::fake()`.
- Symfony `EmporiaConnectBundle` with a configuration tree.
- `FakeClient`/`FakeTransporter` with realistic fixtures and request recording.

### Changed
- `customers()->vehicles()`/`vehicleStatus()` moved to `vehicles()->all()`/`status()`.
- `channels()->usage()` is now `usage()->chart()`. Usage values are returned as reported (kWh per period). Use `watts()` for power.
- `outlets()->update()` and `chargers()->update()` take the outlet/charger object.
- `Unit::WATTS` was removed (the API does not accept it) and `Unit::VOLTAGE` was renamed to `Unit::VOLTS`.
- The client authenticates lazily, on the first API call.

## [0.1.0]
- Initial release
