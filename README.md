# Nytris Memcached

[![Build Status](https://github.com/nytris/memcached/workflows/CI/badge.svg)](https://github.com/nytris/memcached/actions?query=workflow%3ACI)

Standard PECL `ext-memcached` with dynamic mode/auto-discovery support for Memcached AWS ElastiCache.

## Why?
Use the stable and performant standard ext-memcached from PECL while still taking advantage of AWS ElastiCache auto-discovery.

## How does it work?
References to `ext-memcached`'s `Memcached` class will be replaced using [PHP Code Shift][PHP Code Shift]
to references to the `src/Memcached/MemcachedHook.php` class in this package.

Additionally, [session connections can also take advantage of auto-discovery](#sessions-support).

When adding a server, if dynamic mode is enabled for this package in `nytris.config.php`,
the AWS ElastiCache auto-discovery feature will be used, and all discovered nodes added to the `Memcached` instance.

Currently, by default the following references to `Memcached` will be hooked,
but this can be customised using `new MemcachedPackage(memcachedClassHookFilter: ...)`:

- [Stash's Memcached sub-adapter](https://github.com/tedious/Stash/blob/e02ac18/src/Stash/Driver/Sub/Memcached.php#L67)
- [Symfony Cache's Memcached adapter](https://github.com/symfony/cache/blob/5460647/Adapter/MemcachedAdapter.php#L99)

## Usage
Install this package with Composer:

```shell
$ composer install nytris/memcached tasque/tasque tasque/event-loop
```

Configure Nytris platform:

`nytris.config.php`

```php
<?php

declare(strict_types=1);

use Nytris\Boot\BootConfig;
use Nytris\Boot\PlatformConfig;
use Nytris\Memcached\Library\ClientMode;
use Nytris\Memcached\MemcachedPackage;
use Tasque\EventLoop\TasqueEventLoopPackage;
use Tasque\TasquePackage;

$bootConfig = new BootConfig(new PlatformConfig(__DIR__ . '/var/cache/nytris/'));

$bootConfig->installPackage(new TasquePackage(
    // Disabled for this example, but also works with Tasque in preemptive mode.
    preemptive: false
));
$bootConfig->installPackage(new TasqueEventLoopPackage());
$bootConfig->installPackage(new MemcachedPackage(
    // Use dynamic mode/auto-discovery when connecting to an AWS ElastiCache cluster.
    clientMode: ClientMode::DYNAMIC
));

return $bootConfig;
```

### Sessions support

Sessions are supported with the native `ext-memcached` session handling
via `Nytris\Memcached\Session\NativeMemcachedSessionHandler`.

This will perform auto-discovery if enabled when the config endpoint is provided
with either the `session.save_path` INI setting or the `$savePath` constructor argument.

```php
<?php

use Nytris\Memcached\Session\NativeMemcachedSessionHandler;

session_set_save_handler(new NativeMemcachedSessionHandler());
```

## See also
- [PHP Code Shift][PHP Code Shift]

[PHP Code Shift]: https://github.com/asmblah/php-code-shift
