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

namespace Nytris\Memcached\Library;

use Nytris\Memcached\Cluster\ClusterConfigClientInterface;
use Nytris\Memcached\Cluster\ClusterNodeInterface;
use Nytris\Memcached\Environment\EnvironmentInterface;

/**
 * Interface LibraryInterface.
 *
 * Encapsulates an installation of the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface LibraryInterface
{
    /**
     * Fetches the Memcached ElastiCache cluster config client.
     */
    public function getClusterConfigClient(): ClusterConfigClientInterface;

    /**
     * Fetches the Environment.
     */
    public function getEnvironment(): EnvironmentInterface;

    /**
     * Handles dynamic mode/auto-discovery mode for the given session save path.
     */
    public function processSessionSavePath(string $savePath): string;

    /**
     * Resolves the given cluster node to the most optimal host.
     *
     * Uses private IP if available, otherwise resolves the host via DNS if enabled.
     */
    public function resolveOptimalHost(ClusterNodeInterface $clusterNode): string;

    /**
     * Uninstalls this installation of the library.
     */
    public function uninstall(): void;
}
