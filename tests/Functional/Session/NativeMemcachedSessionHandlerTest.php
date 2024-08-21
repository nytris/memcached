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

namespace Nytris\Memcached\Tests\Functional\Session;

use Asmblah\PhpCodeShift\Shifter\Filter\FileFilter;
use Mockery\MockInterface;
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Memcached\Environment\EnvironmentInterface;
use Nytris\Memcached\Exception\InvalidIniSettingException;
use Nytris\Memcached\Library\ClientMode;
use Nytris\Memcached\Library\LibraryInterface;
use Nytris\Memcached\Memcached;
use Nytris\Memcached\MemcachedPackageInterface;
use Nytris\Memcached\Session\NativeMemcachedSessionHandler;
use Nytris\Memcached\Tests\AbstractTestCase;
use React\Socket\Connector;
use Tasque\Core\Scheduler\ContextSwitch\ManualStrategy;
use Tasque\EventLoop\ContextSwitch\FutureTickScheduler;
use Tasque\EventLoop\TasqueEventLoop;
use Tasque\EventLoop\TasqueEventLoopPackageInterface;
use Tasque\Tasque;
use Tasque\TasquePackageInterface;

/**
 * Class NativeMemcachedSessionHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class NativeMemcachedSessionHandlerTest extends AbstractTestCase
{
    private MockInterface&EnvironmentInterface $environment;
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

        $this->environment = mock(EnvironmentInterface::class, [
            'getIniSetting' => 'my value',
            'setIniSetting' => null,
        ]);
        $this->library = mock(LibraryInterface::class, [
            'getEnvironment' => $this->environment,
            'uninstall' => null,
        ]);

        $this->library->allows('processSessionSavePath')
            ->andReturnArg(0)
            ->byDefault();

        Memcached::setLibrary($this->library);
    }

    public function tearDown(): void
    {
        Memcached::uninstall();
        TasqueEventLoop::uninstall();
        Tasque::uninstall();
    }

    public function testConstructorSetsDefaultSavePath(): void
    {
        $this->environment->expects()
            ->setIniSetting('session.save_path', '127.0.0.1:11211')
            ->once();

        new NativeMemcachedSessionHandler();
    }

    public function testConstructorUsesIniSavePathWhenNullGiven(): void
    {
        $this->environment->allows()
            ->getIniSetting('session.save_path')
            ->andReturn('my.save.path:11211');

        $this->environment->expects()
            ->setIniSetting('session.save_path', 'my.save.path:11211')
            ->once();

        new NativeMemcachedSessionHandler(savePath: null);
    }

    public function testConstructorSetsSpecifiedSavePathWhenGiven(): void
    {
        $this->environment->expects()
            ->setIniSetting('session.save_path', 'my.save.path:11211')
            ->once();

        new NativeMemcachedSessionHandler(savePath: 'my.save.path:11211');
    }

    public function testConstructorProcessesSavePathForAutoDiscoveryWhenGiven(): void
    {
        $this->library->allows()
            ->processSessionSavePath('my.save.path:11211')
            ->andReturn('my.autodiscovered.001.node:11211,my.autodiscovered.002.node:11217');

        $this->environment->expects()
            ->setIniSetting(
                'session.save_path',
                'my.autodiscovered.001.node:11211,my.autodiscovered.002.node:11217'
            )
            ->once();

        new NativeMemcachedSessionHandler(savePath: 'my.save.path:11211');
    }

    public function testConstructorProcessesSavePathForAutoDiscoveryFromIniSettingWhenNullGiven(): void
    {
        $this->environment->allows()
            ->getIniSetting('session.save_path')
            ->andReturn('my.save.path:11211');
        $this->library->allows()
            ->processSessionSavePath('my.save.path:11211')
            ->andReturn('my.autodiscovered.001.node:11211,my.autodiscovered.002.node:11217');

        $this->environment->expects()
            ->setIniSetting(
                'session.save_path',
                'my.autodiscovered.001.node:11211,my.autodiscovered.002.node:11217'
            )
            ->once();

        new NativeMemcachedSessionHandler(savePath: null);
    }

    public function testConstructorSetsSessionSaveHandler(): void
    {
        $this->environment->expects()
            ->setIniSetting('session.save_handler', 'memcached')
            ->once();

        new NativeMemcachedSessionHandler();
    }

    public function testConstructorSetsValidIniSettingsGivenInOptions(): void
    {
        $this->environment->expects()
            ->setIniSetting('memcached.sess_prefix', 'abcde.')
            ->once();

        new NativeMemcachedSessionHandler(
            options: [
                'memcached.sess_prefix' => 'abcde.',
            ]
        );
    }

    public function testConstructorRaisesExceptionWhenInvalidIniSettingGivenInOptions(): void
    {
        $this->expectException(InvalidIniSettingException::class);
        $this->expectExceptionMessage('INI setting "some.unsupported.setting" is invalid');

        new NativeMemcachedSessionHandler(
            options: [
                'some.unsupported.setting' => 'my value',
            ]
        );
    }
}
