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

namespace Nytris\Memcached\Tests\Unit\Cluster;

use Mockery\MockInterface;
use Nytris\Memcached\Cluster\ClusterConfig;
use Nytris\Memcached\Cluster\ClusterNodeInterface;
use Nytris\Memcached\Tests\AbstractTestCase;

/**
 * Class ClusterConfigTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ClusterConfigTest extends AbstractTestCase
{
    private ClusterConfig $config;
    private MockInterface&ClusterNodeInterface $node1;
    private MockInterface&ClusterNodeInterface $node2;

    public function setUp(): void
    {
        $this->node1 = mock(ClusterNodeInterface::class);
        $this->node2 = mock(ClusterNodeInterface::class);

        $this->config = new ClusterConfig(123, [$this->node1, $this->node2]);
    }

    public function testGetNodesFetchesTheClusterNodes(): void
    {
        static::assertSame([$this->node1, $this->node2], $this->config->getNodes());
    }

    public function testGetVersionFetchesTheVersion(): void
    {
        static::assertSame(123, $this->config->getVersion());
    }

    public function testGetVersionReturnsNullWhenApplicable(): void
    {
        $config = new ClusterConfig(null, [$this->node1]);

        static::assertNull($config->getVersion());
    }
}
