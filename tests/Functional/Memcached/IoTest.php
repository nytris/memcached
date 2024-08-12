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
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Memcached\Exception\ConnectionClosedUnexpectedlyException;
use Nytris\Memcached\Library\ClientMode;
use Nytris\Memcached\Memcached;
use Nytris\Memcached\Memcached\Io;
use Nytris\Memcached\MemcachedPackageInterface;
use Nytris\Memcached\Tests\AbstractTestCase;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\UnixServer;
use RuntimeException;
use Tasque\Core\Scheduler\ContextSwitch\ManualStrategy;
use Tasque\EventLoop\TasqueEventLoop;
use Tasque\EventLoop\TasqueEventLoopPackageInterface;
use Tasque\Tasque;
use Tasque\TasquePackageInterface;

/**
 * Class IoTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class IoTest extends AbstractTestCase
{
    private ConnectionInterface $connection;
    private Io $io;
    private string $largeData;

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
            mock(MemcachedPackageInterface::class, [
                // Enable dynamic mode, even though this will not be available,
                // to test that we can correctly fall back when connecting to a non-ElastiCache Memcached instance.
                'getClientMode' => ClientMode::DYNAMIC,
                'getMemcachedClassHookFilter' => new FileFilter(dirname(__DIR__) . '/Harness/**')
            ])
        );

        // A massive 920KiB entry.
        $this->largeData = str_repeat('x', 920 * 1024);

        $this->io = new Io();

        $dataDir = dirname(__DIR__, 3) . '/var/test';
        @mkdir($dataDir, 0777, true);
        $unixSocketPath = $dataDir . '/server.sock';
        @unlink($unixSocketPath);
        $server = new UnixServer($unixSocketPath);
        $server->on('connection', function (ConnectionInterface $connection) {
            $connection->on('data', function (string $data) use ($connection) {
                if ($data === "mycommand\r\n") {
                    $connection->write("my result\r\n");
                } elseif ($data === "mylargecommand\r\n") {
                    $connection->write($this->largeData . "\r\n");
                } elseif ($data === "mymulticommand\r\n") {
                    $connection->write("my multiline result\nand some more\r\nEND\r\n");
                } elseif ($data === "mycommandtojustclose\r\n") {
                    $connection->close(); // Just close the connection without writing a response.
                } elseif ($data === "mycommandtobreakclientconnection\r\n") {
                    // Emit an error from the client connection.
                    $this->connection->emit('error', [new RuntimeException('Bang!')]);
                    $connection->write("broken\r\n");
                } elseif ($data === "quit\r\n") {
                    $connection->write("goodbye\r\n");
                    $connection->close();
                }
            });
        });

        $connector = new Connector();
        $this->connection = TasqueEventLoop::await($connector->connect('unix://' . $unixSocketPath));
    }

    public function tearDown(): void
    {
        Memcached::uninstall();
        TasqueEventLoop::uninstall();
        Tasque::uninstall();
    }

    public function testSendCommandResolvesOnceSingleLineResponseReceived(): void
    {
        static::assertSame('my result', $this->io->sendCommand($this->connection, 'mycommand'));
    }

    public function testSendCommandResolvesOnceLargeSingleLineResponseReceived(): void
    {
        // A large command to cause chunking.
        static::assertSame($this->largeData, $this->io->sendCommand($this->connection, 'mylargecommand'));
    }

    public function testSendCommandResolvesOnceMultilineResponseReceived(): void
    {
        static::assertSame(
            "my multiline result\nand some more",
            $this->io->sendCommand($this->connection, 'mymulticommand', true)
        );
    }

    public function testSendCommandRaisesExceptionWhenConnectionClosedUnexpectedlyInSingleLineResponseMode(): void
    {
        $this->expectException(ConnectionClosedUnexpectedlyException::class);
        $this->expectExceptionMessage('Connection was closed unexpectedly');

        $this->io->sendCommand($this->connection, 'mycommandtojustclose');
    }

    public function testSendCommandRaisesExceptionWhenConnectionClosedUnexpectedlyInMultiLineResponseMode(): void
    {
        $this->expectException(ConnectionClosedUnexpectedlyException::class);
        $this->expectExceptionMessage('Connection was closed unexpectedly');

        $this->io->sendCommand($this->connection, 'mycommandtojustclose', multiLineResponse: true);
    }

    public function testSendCommandRaisesExceptionWhenConnectionErrorOccursInSingleLineResponseMode(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bang!');

        $this->io->sendCommand($this->connection, 'mycommandtobreakclientconnection');
    }

    public function testSendCommandRaisesExceptionWhenConnectionErrorOccursInMultilineResponseMode(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bang!');

        $this->io->sendCommand($this->connection, 'mycommandtobreakclientconnection', multiLineResponse: true);
    }
}
