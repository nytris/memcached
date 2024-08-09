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

namespace Nytris\Memcached\Memcached;

use Exception;
use LogicException;
use Nytris\Memcached\Exception\UnsupportedCommandException;
use React\Promise\Promise;
use React\Socket\ConnectionInterface;
use Tasque\EventLoop\TasqueEventLoop;

/**
 * Class Io.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Io implements IoInterface
{
    /**
     * @inheritDoc
     */
    public function sendCommand(
        ConnectionInterface $connection,
        string $command,
        bool $multiLineResponse = false
    ): string {
        return TasqueEventLoop::await(
            new Promise(
                function (callable $resolve, callable $reject) use ($command, $connection, $multiLineResponse) {
                    // TODO: Use ->removeListener(...) for all these when done.

                    $data = '';
                    $connection->on(
                        'data',
                        function (string $chunk) use (
                            $command,
                            &$data,
                            $multiLineResponse,
                            $reject,
                            $resolve
                        ) {
                            $data .= $chunk;

                            if ($data === "ERROR\r\n") {
                                $reject(new UnsupportedCommandException($command));
                            }  elseif ($multiLineResponse) {
                                if (str_ends_with($data, "\r\nEND\r\n")) {
                                    $resolve(substr($data, 0, -7));
                                }
                            } else {
                                if (str_ends_with($data, "\r\n")) {
                                    $resolve(substr($data, 0, -2));
                                }
                            }
                        }
                    );

                    $connection->on('error', function (Exception $exception) use ($reject) {
                        $reject($exception);
                    });

                    $connection->on('close', function () use ($reject) {
                        $reject(new LogicException('Connection was closed unexpectedly'));
                    });

                    $connection->write($command . "\r\n");
                }
            )
        );
    }
}
