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
 * Interface ClusterConfigParserInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface ClusterConfigParserInterface
{
    /**
     * Parses the given cluster config response to a ClusterConfig.
     */
    public function parseClusterConfigResponse(string $clusterConfigResponse): ClusterConfigInterface;
}
