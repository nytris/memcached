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
 * Interface EnvironmentInterface.
 *
 * Abstracts the runtime environment.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface EnvironmentInterface
{
    /**
     * Fetches the value of an INI setting.
     */
    public function getIniSetting(string $name): mixed;

    /**
     * Determines whether an extension is loaded.
     */
    public function isExtensionLoaded(string $name): bool;

    /**
     * Sets the name of an INI setting.
     */
    public function setIniSetting(string $name, mixed $value): void;
}
