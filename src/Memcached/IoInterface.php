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

use React\Socket\ConnectionInterface;

/**
 * Interface IoInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface IoInterface
{
    /**
     * Sends a command to the connected Memcached server.
     */
    public function sendCommand(
        ConnectionInterface $connection,
        string $command,
        bool $multiLineResponse = false
    ): string;
}
