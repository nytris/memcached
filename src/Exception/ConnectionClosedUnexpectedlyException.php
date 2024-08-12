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
 * Class ConnectionClosedUnexpectedlyException.
 *
 * Raised when the Memcached connection is closed unexpectedly.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ConnectionClosedUnexpectedlyException extends RuntimeException
{
}
