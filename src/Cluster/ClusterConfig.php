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
 * Class ClusterConfig.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ClusterConfig implements ClusterConfigInterface
{
    /**
     * @param int $version
     * @param ClusterNodeInterface[] $nodes
     */
    public function __construct(
        private readonly ?int $version,
        private readonly array $nodes
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): ?int
    {
        return $this->version;
    }
}
