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
 * Class ClusterNode.
 *
 * Represents a node of a Memcached ElastiCache cluster.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ClusterNode implements ClusterNodeInterface
{
    public function __construct(
        private readonly string $host,
        private readonly ?string $privateIp,
        private readonly int $port
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @inheritDoc
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @inheritDoc
     */
    public function getPrivateIp(): ?string
    {
        return $this->privateIp;
    }
}
