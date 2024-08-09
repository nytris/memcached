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

use Nytris\Core\Package\PackageFacadeInterface;
use Nytris\Memcached\Cluster\ClusterConfigInterface;
use Nytris\Memcached\Environment\EnvironmentInterface;
use Nytris\Memcached\Library\LibraryInterface;

/**
 * Interface MemcachedInterface.
 *
 * Defines the public facade API for the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface MemcachedInterface extends PackageFacadeInterface
{
    /**
     * Fetches the config of a Memcached ElastiCache cluster given its config endpoint.
     */
    public static function getClusterConfig(string $host, int $port): ClusterConfigInterface;

    /**
     * Fetches the Environment.
     */
    public static function getEnvironment(): EnvironmentInterface;

    /**
     * Fetches the current library installation.
     */
    public static function getLibrary(): LibraryInterface;

    /**
     * Handles dynamic mode/auto-discovery mode for the given session save path.
     */
    public static function processSessionSavePath(string $savePath): string;

    /**
     * Overrides the current library installation with the given one.
     */
    public static function setLibrary(LibraryInterface $library): void;
}
