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
use Memcached as MemcachedExtension;
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Memcached\Library\ClientMode;
use Nytris\Memcached\Memcached;
use Nytris\Memcached\Memcached\MemcachedHook;
use Nytris\Memcached\MemcachedPackageInterface;
use Nytris\Memcached\Tests\AbstractTestCase;
use Nytris\Memcached\Tests\Functional\Harness\TestMemcachedConnector;
use Tasque\Core\Scheduler\ContextSwitch\ManualStrategy;
use Tasque\EventLoop\ContextSwitch\FutureTickScheduler;
use Tasque\EventLoop\TasqueEventLoop;
use Tasque\EventLoop\TasqueEventLoopPackageInterface;
use Tasque\Tasque;
use Tasque\TasquePackageInterface;

/**
 * Class SimpleMemcachedIoTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SimpleMemcachedIoTest extends AbstractTestCase
{
    private MemcachedExtension $memcached;

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
                'getMemcachedClassHookFilter' => new FileFilter(dirname(__DIR__) . '/Harness/**')
            ])
        );

        // Open the connection to Memcached from a separate module that can have the shift applied to it.
        $this->memcached = (new TestMemcachedConnector())->connect();
    }

    public function tearDown(): void
    {
        Memcached::uninstall();
        TasqueEventLoop::uninstall();
        Tasque::uninstall();
    }

    public function testHookedMemcachedIsUsed(): void
    {
        static::assertInstanceOf(MemcachedHook::class, $this->memcached);
    }

    public function testKeysCanBeStoredToAndLaterFetchedFromMemcached(): void
    {
        $this->memcached->addServer('127.0.0.1', 11211);

        $this->memcached->set('my_key', 'my value');

        static::assertSame('my value', $this->memcached->get('my_key'));
    }
}
