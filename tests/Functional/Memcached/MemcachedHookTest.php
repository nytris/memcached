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

namespace Nytris\Memcached\Tests\Functional\Memcached;

use Asmblah\PhpCodeShift\Shifter\Filter\FileFilter;
use Mockery\MockInterface;
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Memcached\Cluster\ClusterConfigClientInterface;
use Nytris\Memcached\Cluster\ClusterConfigInterface;
use Nytris\Memcached\Cluster\ClusterNodeInterface;
use Nytris\Memcached\Library\ClientMode;
use Nytris\Memcached\Library\LibraryInterface;
use Nytris\Memcached\Memcached;
use Nytris\Memcached\Memcached\MemcachedHook;
use Nytris\Memcached\MemcachedPackageInterface;
use Nytris\Memcached\Tests\AbstractTestCase;
use React\Socket\Connector;
use Tasque\Core\Scheduler\ContextSwitch\ManualStrategy;
use Tasque\EventLoop\ContextSwitch\FutureTickScheduler;
use Tasque\EventLoop\TasqueEventLoop;
use Tasque\EventLoop\TasqueEventLoopPackageInterface;
use Tasque\Tasque;
use Tasque\TasquePackageInterface;

/**
 * Class MemcachedHookTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class MemcachedHookTest extends AbstractTestCase
{
    private MockInterface&ClusterConfigClientInterface $clusterConfigClient;
    private MemcachedHook $hookedMemcached;
    private MockInterface&LibraryInterface $library;

    public function setUp(): void
    {
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
        Memcached::install(
            mock(PackageContextInterface::class),
            mock(MemcachedPackageInterface::class, [
                // Enable dynamic mode, even though this will not be available,
                // to test that we can correctly fall back when connecting to a non-ElastiCache Memcached instance.
                'getClientMode' => ClientMode::DYNAMIC,
                'getConnector' => new Connector(),
                'getMemcachedClassHookFilter' => new FileFilter(dirname(__DIR__) . '/Harness/**')
            ])
        );

        $this->clusterConfigClient = mock(ClusterConfigClientInterface::class);
        $this->library = mock(LibraryInterface::class, [
            'getClusterConfigClient' => $this->clusterConfigClient,
            'uninstall' => null,
        ]);

        Memcached::setLibrary($this->library);

        $this->hookedMemcached = new MemcachedHook();
    }

    public function tearDown(): void
    {
        Memcached::uninstall();
        TasqueEventLoop::uninstall();
        Tasque::uninstall();
    }

    public function testAddServerHandlesAutoDiscovery(): void
    {
        $this->clusterConfigClient->allows()
            ->getClusterConfig('my.host.goes.here', 45678)
            ->andReturn(mock(ClusterConfigInterface::class, [
                'getNodes' => [
                    mock(ClusterNodeInterface::class, [
                        'getHost' => '127.0.0.1',
                        'getPort' => 11211,
                    ]),
                ],
            ]));

        $this->hookedMemcached->addServer('my.host.goes.here', 45678);

        static::assertEquals(
            [
                [
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'type' => 'TCP',
                ],
            ],
            $this->hookedMemcached->getServerList()
        );
    }

    public function testAddServersHandlesAutoDiscovery(): void
    {
        $this->clusterConfigClient->allows()
            ->getClusterConfig('my.host.goes.here', 45678)
            ->andReturn(mock(ClusterConfigInterface::class, [
                'getNodes' => [
                    mock(ClusterNodeInterface::class, [
                        'getHost' => '127.0.0.1',
                        'getPort' => 11211,
                    ]),
                ],
            ]));

        $this->hookedMemcached->addServers([
            ['my.host.goes.here', 45678, 0],
        ]);

        static::assertEquals(
            [
                [
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'type' => 'TCP',
                ],
            ],
            $this->hookedMemcached->getServerList()
        );
    }
}
