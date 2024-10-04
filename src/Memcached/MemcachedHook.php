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

use Memcached;
use Nytris\Memcached\Memcached as NytrisMemcached;

/**
 * Class MemcachedHook.
 *
 * Extends the Memcached class defined by ext-memcached.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class MemcachedHook extends Memcached
{
    /**
     * @inheritDoc
     */
    public function addServer($host, $port, $weight = 0): bool
    {
        $clusterConfig = NytrisMemcached::getClusterConfig($host, $port);

        foreach ($clusterConfig->getNodes() as $clusterNode) {
            $optimalHost = NytrisMemcached::resolveOptimalHost($clusterNode);

            if (!parent::addServer($optimalHost, $clusterNode->getPort())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     *
     * @param array{0: string, 1: int, 2: int}[] $servers
     */
    public function addServers(array $servers): bool
    {
        foreach ($servers as [$host, $port, $weight]) {
            if (!$this->addServer($host, $port, $weight)) {
                return false;
            }
        }

        return true;
    }
}
