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

namespace Nytris\Memcached\Resolver;

use Nytris\Memcached\Cluster\ClusterNodeInterface;

/**
 * Interface HostResolverInterface.
 *
 * Resolves the host for a Memcached cluster node.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface HostResolverInterface
{
    /**
     * Resolves the given cluster node to the most optimal host.
     *
     * Uses private IP if available, otherwise resolves the host via DNS if enabled.
     */
    public function resolveOptimalHost(ClusterNodeInterface $clusterNode): string;
}
