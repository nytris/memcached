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

namespace Nytris\Memcached\Serialiser;

use Nytris\Memcached\Cluster\ClusterConfigInterface;

/**
 * Interface SerialiserInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface SerialiserInterface
{
    /**
     * Deserialises the given serialisation of a cluster config.
     */
    public function deserialiseClusterConfig(string $serialisation): ClusterConfigInterface;

    /**
     * Serialises the given cluster config to a string suitable for caching.
     */
    public function serialiseClusterConfig(ClusterConfigInterface $clusterConfig): string;
}
