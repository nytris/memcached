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
use Nytris\Memcached\Cluster\CachingClusterConfigClient;
use Nytris\Memcached\Cluster\ClusterConfigClientInterface;
use Nytris\Memcached\Cluster\ClusterConfigInterface;
use Nytris\Memcached\Tests\AbstractTestCase;

/**
 * Class CachingClusterConfigClientTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CachingClusterConfigClientTest extends AbstractTestCase
{
    private CachingClusterConfigClient $client;
    private MockInterface&ClusterConfigClientInterface $wrappedClient;

    public function setUp(): void
    {
        $this->wrappedClient = mock(ClusterConfigClientInterface::class);

        $this->client = new CachingClusterConfigClient($this->wrappedClient);
    }

    public function testGetClusterConfigFetchesConfigFromWrappedClient(): void
    {
        $config = mock(ClusterConfigInterface::class);
        $this->wrappedClient->allows()
            ->getClusterConfig('my.host', 1234)
            ->andReturn($config);

        static::assertSame($config, $this->client->getClusterConfig('my.host', 1234));
    }

    public function testGetClusterConfigDoesNotRefetchConfigFromWrappedClient(): void
    {
        $config = mock(ClusterConfigInterface::class);

        $this->wrappedClient->expects()
            ->getClusterConfig('my.host', 1234)
            ->andReturn($config)
            ->once();
        $this->wrappedClient->expects('getClusterConfig')
            ->never();
        $this->client->getClusterConfig('my.host', 1234);

        static::assertSame($config, $this->client->getClusterConfig('my.host', 1234));
    }

    public function testGetClusterConfigFetchesADifferentConfigFromWrappedClient(): void
    {
        $myConfig = mock(ClusterConfigInterface::class);
        $yourConfig = mock(ClusterConfigInterface::class);

        $this->wrappedClient->expects()
            ->getClusterConfig('my.host', 1234)
            ->andReturn($myConfig)
            ->once();
        $this->wrappedClient->expects()
            ->getClusterConfig('your.host', 4321)
            ->andReturn($yourConfig)
            ->once();

        static::assertSame($myConfig, $this->client->getClusterConfig('my.host', 1234));
        static::assertSame($yourConfig, $this->client->getClusterConfig('your.host', 4321));
    }
}
