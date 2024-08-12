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

namespace Nytris\Memcached\Tests\Unit\Library;

use Asmblah\PhpCodeShift\CodeShiftInterface;
use Asmblah\PhpCodeShift\Shifter\Filter\FileFilterInterface;
use Asmblah\PhpCodeShift\Shifter\Shift\Shift\ClassHook\ClassHookShiftSpec;
use Asmblah\PhpCodeShift\Shifter\Shift\Spec\ShiftSpecInterface;
use LogicException;
use Memcached;
use Mockery\MockInterface;
use Nytris\Memcached\Cluster\ClusterConfigClientInterface;
use Nytris\Memcached\Environment\EnvironmentInterface;
use Nytris\Memcached\Library\Library;
use Nytris\Memcached\Memcached\MemcachedHook;
use Nytris\Memcached\Session\SavePathProcessorInterface;
use Nytris\Memcached\Tests\AbstractTestCase;

/**
 * Class LibraryTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class LibraryTest extends AbstractTestCase
{
    private MockInterface&ClusterConfigClientInterface $clusterConfigClient;
    private MockInterface&CodeShiftInterface $codeShift;
    private MockInterface&EnvironmentInterface $environment;
    private Library $library;
    private MockInterface&FileFilterInterface $memcachedClassHookFilter;
    private MockInterface&SavePathProcessorInterface $sessionSavePathProcessor;

    public function setUp(): void
    {
        $this->clusterConfigClient = mock(ClusterConfigClientInterface::class);
        $this->codeShift = mock(CodeShiftInterface::class, [
            'shift' => null,
        ]);
        $this->environment = mock(EnvironmentInterface::class);
        $this->memcachedClassHookFilter = mock(FileFilterInterface::class);
        $this->sessionSavePathProcessor = mock(SavePathProcessorInterface::class);

        $this->environment->allows()
            ->isExtensionLoaded('memcached')
            ->andReturnTrue()
            ->byDefault();

        $this->library = new Library(
            $this->environment,
            $this->clusterConfigClient,
            $this->sessionSavePathProcessor,
            $this->codeShift,
            $this->memcachedClassHookFilter
        );
    }

    public function testConstructorRaisesExceptionWhenMemcachedExtensionNotLoaded(): void
    {
        $this->environment->allows()
            ->isExtensionLoaded('memcached')
            ->andReturnFalse();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('ext-memcached PHP extension is not installed');

        new Library(
            $this->environment,
            $this->clusterConfigClient,
            $this->sessionSavePathProcessor,
            $this->codeShift,
            $this->memcachedClassHookFilter
        );
    }

    public function testConstructorAddsShiftToHookMemcachedClass(): void
    {
        $this->codeShift->expects('shift')
            ->andReturnUsing(function (ShiftSpecInterface $shift, $filter) {
                /** @var ClassHookShiftSpec $shift */
                static::assertInstanceOf(ClassHookShiftSpec::class, $shift);
                static::assertSame(Memcached::class, $shift->getClassName());
                static::assertSame(MemcachedHook::class, $shift->getReplacementClassName());
                static::assertSame($this->memcachedClassHookFilter, $filter);
            })
            ->once();

        new Library(
            $this->environment,
            $this->clusterConfigClient,
            $this->sessionSavePathProcessor,
            $this->codeShift,
            $this->memcachedClassHookFilter
        );
    }

    public function testGetClusterConfigClientFetchesTheClient(): void
    {
        static::assertSame($this->clusterConfigClient, $this->library->getClusterConfigClient());
    }

    public function testGetEnvironmentFetchesTheEnvironment(): void
    {
        static::assertSame($this->environment, $this->library->getEnvironment());
    }

    public function testProcessSessionSavePathGoesViaProcessor(): void
    {
        $this->sessionSavePathProcessor->allows()
            ->processSessionSavePath('1.2.3.4:5678')
            ->andReturn('my result');

        static::assertSame('my result', $this->library->processSessionSavePath('1.2.3.4:5678'));
    }
}
