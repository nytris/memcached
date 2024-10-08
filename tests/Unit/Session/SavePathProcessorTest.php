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

namespace Nytris\Memcached\Tests\Unit\Session;

use Mockery\MockInterface;
use Nytris\Memcached\Cluster\ClusterConfigClientInterface;
use Nytris\Memcached\Cluster\ClusterConfigInterface;
use Nytris\Memcached\Cluster\ClusterNodeInterface;
use Nytris\Memcached\Resolver\HostResolverInterface;
use Nytris\Memcached\Session\SavePathProcessor;
use Nytris\Memcached\Tests\AbstractTestCase;

/**
 * Class SavePathProcessorTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SavePathProcessorTest extends AbstractTestCase
{
    private MockInterface&ClusterConfigClientInterface $clusterConfigClient;
    private MockInterface&HostResolverInterface $hostResolver;
    private SavePathProcessor $savePathProcessor;

    public function setUp(): void
    {
        $this->clusterConfigClient = mock(ClusterConfigClientInterface::class);
        $this->hostResolver = mock(HostResolverInterface::class);

        $this->hostResolver->allows('resolveOptimalHost')
            ->andReturnUsing(fn (ClusterNodeInterface $clusterNode) => 'optimal.' . $clusterNode->getHost())
            ->byDefault();

        $this->savePathProcessor = new SavePathProcessor(
            $this->clusterConfigClient,
            $this->hostResolver
        );
    }

    public function testProcessSessionSavePathProcessesASingleHost(): void
    {
        $this->clusterConfigClient->allows()
            ->getClusterConfig('10.20.30.40', 1234)
            ->andReturn(mock(ClusterConfigInterface::class, [
                'getNodes' => [
                    mock(ClusterNodeInterface::class, [
                        'getHost' => '100.1.2.3',
                        'getPort' => 321,
                    ]),
                    mock(ClusterNodeInterface::class, [
                        'getHost' => '200.3.2.1',
                        'getPort' => 123,
                    ]),
                ],
            ]));

        static::assertSame(
            'optimal.100.1.2.3:321,optimal.200.3.2.1:123',
            $this->savePathProcessor->processSessionSavePath('10.20.30.40:1234')
        );
    }

    public function testProcessSessionSavePathIgnoresMultipleHosts(): void
    {
        $this->clusterConfigClient->expects('getClusterConfig')
            ->never();

        static::assertSame(
            '10.20.30.40:1234,4.5.6.7:8910',
            $this->savePathProcessor->processSessionSavePath('10.20.30.40:1234,4.5.6.7:8910')
        );
    }

    public function testProcessSessionSavePathIgnoresOptionalHostWeight(): void
    {
        $this->clusterConfigClient->allows()
            ->getClusterConfig('10.20.30.40', 1234)
            ->andReturn(mock(ClusterConfigInterface::class, [
                'getNodes' => [
                    mock(ClusterNodeInterface::class, [
                        'getHost' => '100.1.2.3',
                        'getPort' => 321,
                    ]),
                    mock(ClusterNodeInterface::class, [
                        'getHost' => '200.3.2.1',
                        'getPort' => 123,
                    ]),
                ],
            ]));

        static::assertSame(
            'optimal.100.1.2.3:321,optimal.200.3.2.1:123',
            $this->savePathProcessor->processSessionSavePath('10.20.30.40:1234:42')
        );
    }

    public function testProcessSessionSavePathSupportsPortBeingOmitted(): void
    {
        $this->clusterConfigClient->allows()
            ->getClusterConfig('10.20.30.40', 11211)
            ->andReturn(mock(ClusterConfigInterface::class, [
                'getNodes' => [
                    mock(ClusterNodeInterface::class, [
                        'getHost' => '100.1.2.3',
                        'getPort' => 321,
                    ]),
                    mock(ClusterNodeInterface::class, [
                        'getHost' => '200.3.2.1',
                        'getPort' => 123,
                    ]),
                ],
            ]));

        static::assertSame(
            'optimal.100.1.2.3:321,optimal.200.3.2.1:123',
            $this->savePathProcessor->processSessionSavePath('10.20.30.40')
        );
    }
}
