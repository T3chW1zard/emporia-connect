# t3chw1zard/emporia-connect

A framework-agnostic PHP 8.2+ client for the Emporia Vue energy monitoring API.
A full PHP port of [PyEmVue](https://github.com/magico13/PyEmVue).

## Installation

```bash
composer require t3chw1zard/emporia-connect
```

Guzzle is included automatically. No additional HTTP client needed.

## Usage

```php
use T3chW1zard\EmporiaConnect\EmporiaConnect;
use T3chW1zard\EmporiaConnect\Enums\Scale;

$client = EmporiaConnect::client(
    username: 'user@example.com',
    password: 'secret',
);

$customer = $client->customers()->me();
$devices  = $client->devices()->all();
$usage    = $client->channels()->usage(12345, '1', Scale::MINUTE);
```

See `docs/laravel.md` and `docs/symfony.md` for framework integration.
