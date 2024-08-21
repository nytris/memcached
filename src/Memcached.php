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

use Asmblah\PhpCodeShift\CodeShift;
use InvalidArgumentException;
use LogicException;
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Core\Package\PackageInterface;
use Nytris\Memcached\Cluster\CachingClusterConfigClient;
use Nytris\Memcached\Cluster\ClusterConfigClient;
use Nytris\Memcached\Cluster\ClusterConfigInterface;
use Nytris\Memcached\Cluster\ClusterConfigParser;
use Nytris\Memcached\Environment\Environment;
use Nytris\Memcached\Environment\EnvironmentInterface;
use Nytris\Memcached\Library\Library;
use Nytris\Memcached\Library\LibraryInterface;
use Nytris\Memcached\Memcached\Io;
use Nytris\Memcached\Session\SavePathProcessor;

/**
 * Class Memcached.
 *
 * Defines the public facade API for the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Memcached implements MemcachedInterface
{
    private static bool $bootstrapped = false;
    private static ?LibraryInterface $library = null;

    /**
     * Bootstrapping only ever happens once, either via Composer's file-autoload mechanism
     * or via Memcached::install(...), whichever happens first.
     */
    public static function bootstrap(): void
    {
        if (self::$bootstrapped) {
            return;
        }

        self::$bootstrapped = true;
    }

    /**
     * @inheritDoc
     */
    public static function getClusterConfig(string $host, int $port): ClusterConfigInterface
    {
        return self::getLibrary()->getClusterConfigClient()->getClusterConfig($host, $port);
    }

    /**
     * @inheritDoc
     */
    public static function getEnvironment(): EnvironmentInterface
    {
        return self::getLibrary()->getEnvironment();
    }

    /**
     * @inheritDoc
     */
    public static function getLibrary(): LibraryInterface
    {
        if (!self::$library) {
            throw new LogicException(
                'Library is not installed - did you forget to install this package in nytris.config.php?'
            );
        }

        return self::$library;
    }

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return 'memcached';
    }

    /**
     * @inheritDoc
     */
    public static function getVendor(): string
    {
        return 'nytris';
    }

    /**
     * @inheritDoc
     */
    public static function install(PackageContextInterface $packageContext, PackageInterface $package): void
    {
        if (!$package instanceof MemcachedPackageInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Package config must be a %s but it was a %s',
                    MemcachedPackageInterface::class,
                    $package::class
                )
            );
        }

        self::bootstrap();

        $clusterConfigClient = new CachingClusterConfigClient(
            new ClusterConfigClient(
                $package->getClientMode(),
                $package->getConnector(),
                new Io(),
                new ClusterConfigParser()
            )
        );

        self::$library = new Library(
            new Environment(),
            $clusterConfigClient,
            new SavePathProcessor($clusterConfigClient),
            new CodeShift(),
            $package->getMemcachedClassHookFilter()
        );
    }

    /**
     * @inheritDoc
     */
    public static function isInstalled(): bool
    {
        return self::$library !== null;
    }

    /**
     * @inheritDoc
     */
    public static function processSessionSavePath(string $savePath): string
    {
        return self::getLibrary()->processSessionSavePath($savePath);
    }

    /**
     * @inheritDoc
     */
    public static function setLibrary(LibraryInterface $library): void
    {
        if (self::$library !== null) {
            self::$library->uninstall();
        }

        self::$library = $library;
    }

    /**
     * @inheritDoc
     */
    public static function uninstall(): void
    {
        if (self::$library === null) {
            // Not yet installed anyway; nothing to do.
            return;
        }

        self::$library->uninstall();
        self::$library = null;
    }
}
