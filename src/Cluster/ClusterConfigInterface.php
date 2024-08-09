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

namespace Nytris\Memcached\Cluster;

/**
 * Interface ClusterConfigInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface ClusterConfigInterface
{
    /**
     * Fetches the nodes of the cluster in dynamic client mode,
     * or just the single given node in static mode.
     *
     * @return array<ClusterNodeInterface>
     */
    public function getNodes(): array;

    /**
     * Fetches the cluster version returned from the `config get cluster` Memcached ElastiCache command
     * in dynamic client mode, or null in static mode.
     */
    public function getVersion(): ?int;
}
