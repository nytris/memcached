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
 * Interface ClusterConfigClientInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface ClusterConfigClientInterface
{
    /**
     * Fetches the config of a Memcached ElastiCache cluster given its config endpoint.
     */
    public function getClusterConfig(string $host, int $port): ClusterConfigInterface;
}
