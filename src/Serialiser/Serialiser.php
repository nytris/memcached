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
 * Class Serialiser.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Serialiser implements SerialiserInterface
{
    /**
     * @inheritDoc
     */
    public function deserialiseClusterConfig(string $serialisation): ClusterConfigInterface
    {
        return unserialize($serialisation);
    }

    /**
     * @inheritDoc
     */
    public function serialiseClusterConfig(ClusterConfigInterface $clusterConfig): string
    {
        return serialize($clusterConfig);
    }
}
