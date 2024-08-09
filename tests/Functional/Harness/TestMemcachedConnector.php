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

namespace Nytris\Memcached\Tests\Functional\Harness;

use Memcached;

/**
 * Class TestMemcachedConnector.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TestMemcachedConnector
{
    public function connect(): Memcached
    {
        // Open the connection here, where the shift can have been applied.
        return new Memcached();
    }
}
