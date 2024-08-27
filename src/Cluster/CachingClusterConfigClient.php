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

use Nytris\Memcached\Serialiser\SerialiserInterface;
use React\Cache\CacheInterface;
use Tasque\EventLoop\TasqueEventLoop;

/**
 * Class CachingClusterConfigClient.
 *
 * Caches cluster config (auto-discovered if applicable) in a ReactPHP cache.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CachingClusterConfigClient implements ClusterConfigClientInterface
{
    public function __construct(
        private readonly ClusterConfigClientInterface $wrappedClient,
        private readonly SerialiserInterface $serialiser,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getClusterConfig(string $host, int $port): ClusterConfigInterface
    {
        // Use the endpoint as a unique identifier for the cluster.
        $endpoint = $host . ':' . $port;

        $clusterConfigSerialisation = TasqueEventLoop::await($this->cache->get($endpoint));

        if ($clusterConfigSerialisation !== null) {
            // We have already fetched this cluster config, and it has not expired,
            // so we can reuse it to save on an additional auto-discovery.
            return $this->serialiser->deserialiseClusterConfig($clusterConfigSerialisation);
        }

        $clusterConfig = $this->wrappedClient->getClusterConfig($host, $port);

        TasqueEventLoop::await(
            $this->cache->set(
                $endpoint,
                $this->serialiser->serialiseClusterConfig($clusterConfig)
            )
        );

        return $clusterConfig;
    }
}
