<?php

/*
 * Nytris Memcached - Memcached with dynamic mode/auto-discovery support for AWS ElastiCache.
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/memcached/
 *
 * Released under the MIT license.
 * https://github.com/nytris/memcached/raw/main/MIT-LICENSE.txt
 */

declare(strict_types=1);

namespace Nytris\Memcached\Environment;

/**
 * Class Environment.
 *
 * Abstracts the runtime environment.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Environment implements EnvironmentInterface
{
    /**
     * @inheritDoc
     */
    public function getIniSetting(string $name): mixed
    {
        return ini_get($name);
    }

    /**
     * @inheritDoc
     */
    public function isExtensionLoaded(string $name): bool
    {
        return extension_loaded($name);
    }

    /**
     * @inheritDoc
     */
    public function setIniSetting(string $name, mixed $value): void
    {
        ini_set($name, $value);
    }
}
