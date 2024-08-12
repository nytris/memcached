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

namespace Nytris\Memcached\Tests\Functional\Hook;

use Mockery\MockInterface;
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Memcached\Cluster\ClusterConfigClientInterface;
use Nytris\Memcached\Cluster\ClusterConfigInterface;
use Nytris\Memcached\Cluster\ClusterNodeInterface;
use Nytris\Memcached\Library\ClientMode;
use Nytris\Memcached\Library\LibraryInterface;
use Nytris\Memcached\Memcached;
use Nytris\Memcached\MemcachedPackage;
use Nytris\Memcached\Tests\AbstractTestCase;
use Stash\Driver\Memcache;
use Stash\Pool;
use Tasque\Core\Scheduler\ContextSwitch\ManualStrategy;
use Tasque\EventLoop\TasqueEventLoop;
use Tasque\EventLoop\TasqueEventLoopPackageInterface;
use Tasque\Tasque;
use Tasque\TasquePackageInterface;

/**
 * Class StashMemcachedSubDriverHookTest.
 *
 * Tests that the Stash Memcached sub-driver is correctly hooked.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StashMemcachedSubDriverHookTest extends AbstractTestCase
{
    private MockInterface&ClusterConfigClientInterface $clusterConfigClient;
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
            mock(TasqueEventLoopPackageInterface::class)
        );
        Memcached::install(
            mock(PackageContextInterface::class),
            new MemcachedPackage(clientMode: ClientMode::DYNAMIC)
        );

        $this->clusterConfigClient = mock(ClusterConfigClientInterface::class);
        $this->library = mock(LibraryInterface::class, [
            'getClusterConfigClient' => $this->clusterConfigClient,
            'uninstall' => null,
        ]);

        Memcached::setLibrary($this->library);
    }

    public function tearDown(): void
    {
        Memcached::uninstall();
        TasqueEventLoop::uninstall();
        Tasque::uninstall();
    }

    public function testSubDriverUsesMemcachedHookClass(): void
    {
        $this->clusterConfigClient->allows()
            ->getClusterConfig('my.elasticache.config.endpoint', 12345)
            ->andReturn(mock(ClusterConfigInterface::class, [
                'getNodes' => [
                    mock(ClusterNodeInterface::class, [
                        'getHost' => '127.0.0.1',
                        'getPort' => 11211,
                    ]),
                ],
            ]));
        $driver = new Memcache([
            'extension' => 'memcached',
            'servers' => [
                // Use a fake ElastiCache endpoint, which will have "auto-discovery" performed on it
                // by the stub above, repointing back to the test Memcached instance.
                ['host' => 'my.elasticache.config.endpoint', 'port' => 12345],
            ],
        ]);
        $pool = new Pool($driver);
        $pool->getItem('my.key')
            ->set('my value')
            ->save();

        static::assertSame('my value', $pool->getItem('my.key')->get());
    }
}