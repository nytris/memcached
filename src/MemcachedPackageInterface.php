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

namespace Nytris\Memcached;

use Asmblah\PhpCodeShift\Shifter\Filter\FileFilterInterface;
use Nytris\Core\Package\PackageInterface;
use Nytris\Memcached\Library\ClientMode;
use React\Cache\CacheInterface;
use React\Dns\Resolver\ResolverInterface;
use React\Socket\ConnectorInterface;

/**
 * Interface MemcachedPackageInterface.
 *
 * Configures the installation of Nytris Memcached.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface MemcachedPackageInterface extends PackageInterface
{
    /**
     * Fetches whether to use dynamic or static Memcached client mode.
     */
    public function getClientMode(): ClientMode;

    /**
     * Fetches the ReactPHP cache to use for caching cluster configurations.
     */
    public function getClusterConfigCache(string $packageCachePath): CacheInterface;

    /**
     * Fetches the ReactPHP socket connector to use.
     */
    public function getConnector(string $packageCachePath): ConnectorInterface;

    /**
     * Fetches the ReactPHP DNS resolver to use, if any.
     */
    public function getDnsResolver(string $packageCachePath): ?ResolverInterface;

    /**
     * Fetches the filter for which files to hook the Memcached built-in class for.
     */
    public function getMemcachedClassHookFilter(): FileFilterInterface;
}
