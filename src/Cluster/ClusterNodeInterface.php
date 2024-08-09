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
 * Interface ClusterNodeInterface.
 *
 * Represents a node of a Memcached ElastiCache cluster.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface ClusterNodeInterface
{
    /**
     * Fetches the node's CNAME hostname.
     */
    public function getHost(): string;

    /**
     * Fetches the node's port.
     */
    public function getPort(): int;

    /**
     * Fetches the private IP address returned from ElastiCache: if not provided, null will be returned.
     */
    public function getPrivateIp(): ?string;
}
