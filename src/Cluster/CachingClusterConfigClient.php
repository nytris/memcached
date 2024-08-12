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
 * Class CachingClusterConfigClient.
 *
 * Caches cluster config (auto-discovered if applicable) in memory for the current program.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CachingClusterConfigClient implements ClusterConfigClientInterface
{
    /**
     * @var array<string, ClusterConfigInterface>
     */
    private array $configByEndpoint = [];

    public function __construct(
        private readonly ClusterConfigClientInterface $wrappedClient
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getClusterConfig(string $host, int $port): ClusterConfigInterface
    {
        $endpoint = $host . ':' . $port;

        return $this->configByEndpoint[$endpoint] ??
            ($this->configByEndpoint[$endpoint] = $this->wrappedClient->getClusterConfig($host, $port));
    }
}
