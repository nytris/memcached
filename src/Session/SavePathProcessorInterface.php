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

namespace Nytris\Memcached\Session;

/**
 * Interface SavePathProcessorInterface.
 *
 * Handles auto-discovery of AWS Memcached ElastiCache nodes for the session connection.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface SavePathProcessorInterface
{
    /**
     * Handles dynamic mode/auto-discovery mode for the given session save path.
     */
    public function processSessionSavePath(string $savePath): string;
}
