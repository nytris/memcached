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
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Memcached\Cluster\CachingClusterConfigClient;
use Nytris\Memcached\Cluster\ClusterConfigClientInterface;
use Nytris\Memcached\Cluster\ClusterConfigInterface;
use Nytris\Memcached\Serialiser\SerialiserInterface;
use Nytris\Memcached\Tests\AbstractTestCase;
use React\Cache\CacheInterface;
use React\Promise\Promise;
use Tasque\Core\Scheduler\ContextSwitch\ManualStrategy;
use Tasque\EventLoop\ContextSwitch\FutureTickScheduler;
use Tasque\EventLoop\TasqueEventLoop;
use Tasque\EventLoop\TasqueEventLoopPackageInterface;
use Tasque\Tasque;
use Tasque\TasquePackageInterface;

/**
 * Class CachingClusterConfigClientTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CachingClusterConfigClientTest extends AbstractTestCase
{
    private CachingClusterConfigClient $client;
    private MockInterface&CacheInterface $clusterConfigCache;
    private MockInterface&SerialiserInterface $serialiser;
    private MockInterface&ClusterConfigClientInterface $wrappedClient;

    public function setUp(): void
    {
        $this->clusterConfigCache = mock(CacheInterface::class);
        $this->serialiser = mock(SerialiserInterface::class);
        $this->wrappedClient = mock(ClusterConfigClientInterface::class);

        Tasque::install(
            mock(PackageContextInterface::class),
            mock(TasquePackageInterface::class, [
                'getSchedulerStrategy' => new ManualStrategy(),
                'isPreemptive' => false,
            ])
        );
        TasqueEventLoop::install(
            mock(PackageContextInterface::class),
            mock(TasqueEventLoopPackageInterface::class, [
                'getContextSwitchInterval' => TasqueEventLoopPackageInterface::DEFAULT_CONTEXT_SWITCH_INTERVAL,
                // Switch every tick to make tests deterministic.
                'getContextSwitchScheduler' => new FutureTickScheduler(),
                'getEventLoop' => null,
            ])
        );

        $this->client = new CachingClusterConfigClient(
            $this->wrappedClient,
            $this->serialiser,
            $this->clusterConfigCache
        );
    }

    public function tearDown(): void
    {
        TasqueEventLoop::uninstall();
        Tasque::uninstall();
    }

    public function testGetClusterConfigFetchesConfigFromWrappedClientWhenNotYetCached(): void
    {
        $config = mock(ClusterConfigInterface::class);
        $this->wrappedClient->allows()
            ->getClusterConfig('my.host', 1234)
            ->andReturn($config);
        $this->serialiser->allows()
            ->serialiseClusterConfig($config)
            ->andReturn('my serialised config');
        $this->clusterConfigCache->allows()
            ->get('my.host:1234')
            ->andReturn(new Promise(fn (callable $resolve) => $resolve(null)));
        $this->clusterConfigCache->allows()
            ->set('my.host:1234', 'my serialised config')
            ->andReturn(new Promise(fn (callable $resolve) => $resolve(true)));

        static::assertSame($config, $this->client->getClusterConfig('my.host', 1234));
    }

    public function testGetClusterConfigDoesNotRefetchConfigFromWrappedClient(): void
    {
        $config = mock(ClusterConfigInterface::class);
        $this->clusterConfigCache->allows()
            ->get('my.host:1234')
            ->andReturn(new Promise(fn (callable $resolve) => $resolve('my serialised config')));
        $this->serialiser->allows()
            ->deserialiseClusterConfig('my serialised config')
            ->andReturn($config);

        $this->wrappedClient->expects('getClusterConfig')
            ->never();
        $this->client->getClusterConfig('my.host', 1234);

        static::assertSame($config, $this->client->getClusterConfig('my.host', 1234));
    }

    public function testGetClusterConfigFetchesADifferentConfigFromWrappedClient(): void
    {
        $myConfig = mock(ClusterConfigInterface::class);
        $this->clusterConfigCache->allows()
            ->get('my.host:1234')
            ->andReturn(new Promise(fn (callable $resolve) => $resolve('my serialised config')));
        $this->serialiser->allows()
            ->deserialiseClusterConfig('my serialised config')
            ->andReturn($myConfig);
        $yourConfig = mock(ClusterConfigInterface::class);
        $this->clusterConfigCache->allows()
            ->get('your.host:4321')
            ->andReturn(new Promise(fn (callable $resolve) => $resolve(null)));
        $this->serialiser->allows()
            ->serialiseClusterConfig($yourConfig)
            ->andReturn('your serialised config');
        $this->clusterConfigCache->allows()
            ->set('your.host:4321', 'your serialised config')
            ->andReturn(new Promise(fn (callable $resolve) => $resolve(true)));

        $this->wrappedClient->expects()
            ->getClusterConfig('my.host', 1234)
            ->never();
        $this->wrappedClient->expects()
            ->getClusterConfig('your.host', 4321)
            ->andReturn($yourConfig)
            ->once();

        static::assertSame($myConfig, $this->client->getClusterConfig('my.host', 1234));
        static::assertSame($yourConfig, $this->client->getClusterConfig('your.host', 4321));
    }
}
