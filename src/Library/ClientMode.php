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

namespace Nytris\Memcached\Library;

/**
 * Enum ClientMode.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
enum ClientMode
{
    /**
     * AWS-specific dynamic mode, where nodes are auto-discovered.
     */
    case DYNAMIC;

    /**
     * Standard mode, where all Memcached nodes must be added manually.
     */
    case STATIC;
}
