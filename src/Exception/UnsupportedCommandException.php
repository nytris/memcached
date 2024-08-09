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

namespace Nytris\Memcached\Exception;

use RuntimeException;

/**
 * Class UnsupportedCommandException.
 *
 * Raised when a command returns an "ERROR" response.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class UnsupportedCommandException extends RuntimeException
{
    public function __construct(string $command)
    {
        parent::__construct(sprintf('Unsupported Memcached command "%s"', $command));
    }
}
