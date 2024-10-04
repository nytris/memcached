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

namespace Nytris\Memcached\Tests\Unit\Resolver;

use Mockery\MockInterface;
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Memcached\Cluster\ClusterNodeInterface;
use Nytris\Memcached\Resolver\HostResolver;
use Nytris\Memcached\Tests\AbstractTestCase;
use React\Dns\Resolver\ResolverInterface;
use React\Promise\Promise;
use Tasque\Core\Scheduler\ContextSwitch\ManualStrategy;
use Tasque\EventLoop\ContextSwitch\FutureTickScheduler;
use Tasque\EventLoop\TasqueEventLoop;
use Tasque\EventLoop\TasqueEventLoopPackageInterface;
use Tasque\Tasque;
use Tasque\TasquePackageInterface;

/**
 * Class HostResolverTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class HostResolverTest extends AbstractTestCase
{
    private MockInterface&ClusterNodeInterface $clusterNode;
    private MockInterface&ResolverInterface $dnsResolver;
    private HostResolver $hostResolver;

    public function setUp(): void
    {
        $this->clusterNode = mock(ClusterNodeInterface::class, [
            'getHost' => 'my.node.host',
            'getPrivateIp' => '1.2.3.4',
        ]);
        $this->dnsResolver = mock(ResolverInterface::class);

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

        $this->hostResolver = new HostResolver($this->dnsResolver);
    }

    public function tearDown(): void
    {
        TasqueEventLoop::uninstall();
        Tasque::uninstall();
    }

    public function testResolveOptimalHostPrefersPrivateIpWhenAvailable(): void
    {
        $this->dnsResolver->expects('resolve')
            ->never();

        static::assertSame('1.2.3.4', $this->hostResolver->resolveOptimalHost($this->clusterNode));
    }

    public function testResolveOptimalHostReturnsResolvedHostWhenDnsResolverIsSetButPrivateIpIsNull(): void
    {
        $this->clusterNode->allows()
            ->getPrivateIp()
            ->andReturnNull();
        $this->dnsResolver->allows()
            ->resolve('my.node.host')
            ->andReturn(new Promise(static fn (callable $resolve) => $resolve('my.optimal.node.host')));

        static::assertSame(
            'my.optimal.node.host',
            $this->hostResolver->resolveOptimalHost($this->clusterNode)
        );
    }

    public function testResolveOptimalHostReturnsHostWhenNoDnsResolverIsSetAndPrivateIpIsNull(): void
    {
        $this->clusterNode->allows()
            ->getPrivateIp()
            ->andReturnNull();
        $this->hostResolver = new HostResolver(dnsResolver: null);

        static::assertSame('my.node.host', $this->hostResolver->resolveOptimalHost($this->clusterNode));
    }
}
