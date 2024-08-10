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
use Nytris\Memcached\Cluster\ClusterConfigClient;
use Nytris\Memcached\Cluster\ClusterConfigInterface;
use Nytris\Memcached\Cluster\ClusterConfigParserInterface;
use Nytris\Memcached\Exception\InvalidVersionResponseException;
use Nytris\Memcached\Exception\UnsupportedCommandException;
use Nytris\Memcached\Library\ClientMode;
use Nytris\Memcached\Memcached\IoInterface;
use Nytris\Memcached\Tests\AbstractTestCase;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ConnectorInterface;
use Tasque\EventLoop\Library\LibraryInterface as TasqueEventLoopLibraryInterface;
use Tasque\EventLoop\TasqueEventLoop;

/**
 * Class ClusterConfigClientTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ClusterConfigClientTest extends AbstractTestCase
{
    private ClusterConfigClient $client;
    private MockInterface&ClusterConfigParserInterface $clusterConfigParser;
    private MockInterface&ConnectionInterface $connection;
    /**
     * @var MockInterface&PromiseInterface<ConnectionInterface>
     */
    private MockInterface&PromiseInterface $connectionPromise;
    private MockInterface&ConnectorInterface $connector;
    private MockInterface&IoInterface $io;
    private MockInterface&TasqueEventLoopLibraryInterface $tasqueEventLoopLibrary;

    public function setUp(): void
    {
        $this->clusterConfigParser = mock(ClusterConfigParserInterface::class);
        $this->connection = mock(ConnectionInterface::class, [
            'close' => null,
        ]);
        $this->connectionPromise = mock(PromiseInterface::class);
        $this->connector = mock(ConnectorInterface::class);
        $this->io = mock(IoInterface::class);
        $this->tasqueEventLoopLibrary = mock(TasqueEventLoopLibraryInterface::class, [
            'uninstall' => null,
        ]);

        $this->io->allows()
            ->sendCommand($this->connection, 'version')
            ->andReturn('VERSION 1.2.3')
            ->byDefault();

        $this->tasqueEventLoopLibrary->allows()
            ->await($this->connectionPromise)
            ->andReturn($this->connection)
            ->byDefault();

        TasqueEventLoop::setLibrary($this->tasqueEventLoopLibrary);

        $this->client = new ClusterConfigClient(
            ClientMode::DYNAMIC,
            $this->connector,
            $this->io,
            $this->clusterConfigParser
        );
    }

    public function tearDown(): void
    {
        TasqueEventLoop::uninstall();
    }

    public function testGetClusterConfigReturnsGivenHostAndPortInStaticMode(): void
    {
        $this->client = new ClusterConfigClient(
            ClientMode::STATIC,
            $this->connector,
            $this->io,
            $this->clusterConfigParser
        );

        $clusterConfig = $this->client->getClusterConfig('my.host', 1234);

        static::assertNull($clusterConfig->getVersion());
        $nodes = $clusterConfig->getNodes();
        static::assertCount(1, $nodes);
        static::assertSame('my.host', $nodes[0]->getHost());
        static::assertNull($nodes[0]->getPrivateIp());
        static::assertSame(1234, $nodes[0]->getPort());
    }

    public function testGetClusterConfigRaisesExceptionOnInvalidVersionCommandResponse(): void
    {
        $this->connector->allows()
            ->connect('tcp://my.host:1234')
            ->andReturn($this->connectionPromise);
        $this->io->allows()
            ->sendCommand($this->connection, 'version')
            ->andReturn('IAMINVALID 1.2.3')
            ->byDefault();

        $this->expectException(InvalidVersionResponseException::class);
        $this->expectExceptionMessage('Unexpected "version" command response: "IAMINVALID 1.2.3"');

        $this->client->getClusterConfig('my.host', 1234);
    }

    public function testGetClusterConfigReturnsGivenHostAndPortInDynamicModeButWhenUnsuccessful(): void
    {
        $this->connector->allows()
            ->connect('tcp://my.host:1234')
            ->andReturn($this->connectionPromise);
        $this->io->allows()
            ->sendCommand($this->connection, 'config get cluster', true)
            ->andThrow(new UnsupportedCommandException('config get cluster'));

        $clusterConfig = $this->client->getClusterConfig('my.host', 1234);

        static::assertNull($clusterConfig->getVersion());
        $nodes = $clusterConfig->getNodes();
        static::assertCount(1, $nodes);
        static::assertSame('my.host', $nodes[0]->getHost());
        static::assertNull($nodes[0]->getPrivateIp());
        static::assertSame(1234, $nodes[0]->getPort());
    }

    public function testGetClusterConfigClosesConnectionInDynamicModeButWhenUnsuccessful(): void
    {
        $this->connector->allows()
            ->connect('tcp://my.host:1234')
            ->andReturn($this->connectionPromise);
        $this->io->allows()
            ->sendCommand($this->connection, 'config get cluster', true)
            ->andThrow(new UnsupportedCommandException('config get cluster'));

        $this->connection->expects()
            ->close()
            ->once();

        $this->client->getClusterConfig('my.host', 1234);
    }

    public function testGetClusterConfigReturnsParsedConfigInDynamicModeWhenSuccessful(): void
    {
        $this->connector->allows()
            ->connect('tcp://my.host:1234')
            ->andReturn($this->connectionPromise);
        $this->io->allows()
            ->sendCommand($this->connection, 'config get cluster', true)
            ->andReturn('my response');
        $clusterConfig = mock(ClusterConfigInterface::class);
        $this->clusterConfigParser->allows()
            ->parseClusterConfigResponse('my response')
            ->andReturn($clusterConfig);

        static::assertSame(
            $clusterConfig,
            $this->client->getClusterConfig('my.host', 1234)
        );
    }

    public function testGetClusterConfigClosesConnectionInDynamicModeWhenSuccessful(): void
    {
        $this->connector->allows()
            ->connect('tcp://my.host:1234')
            ->andReturn($this->connectionPromise);
        $this->io->allows()
            ->sendCommand($this->connection, 'config get cluster', true)
            ->andReturn('my response');
        $clusterConfig = mock(ClusterConfigInterface::class);
        $this->clusterConfigParser->allows()
            ->parseClusterConfigResponse('my response')
            ->andReturn($clusterConfig);

        $this->connection->expects()
            ->close()
            ->once();

        $this->client->getClusterConfig('my.host', 1234);
    }
}
