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

use Nytris\Memcached\Cluster\ClusterNode;
use Nytris\Memcached\Tests\AbstractTestCase;

/**
 * Class ClusterNodeTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ClusterNodeTest extends AbstractTestCase
{
    private ClusterNode $node;

    public function setUp(): void
    {
        $this->node = new ClusterNode('my.host', '1.2.3.4', 1234);
    }

    public function testGetHostFetchesTheHost(): void
    {
        static::assertSame('my.host', $this->node->getHost());
    }

    public function testGetPortFetchesThePort(): void
    {
        static::assertSame(1234, $this->node->getPort());
    }

    public function testGetPrivateIpFetchesThePrivateIp(): void
    {
        static::assertSame('1.2.3.4', $this->node->getPrivateIp());
    }

    public function testGetPrivateIpReturnsNullWhenApplicable(): void
    {
        $node = new ClusterNode('my.host', null, 1234);

        static::assertNull($node->getPrivateIp());
    }
}
