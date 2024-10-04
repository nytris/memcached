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

namespace Nytris\Memcached\Resolver;

use Nytris\Memcached\Cluster\ClusterNodeInterface;
use React\Dns\Resolver\ResolverInterface;
use Tasque\EventLoop\TasqueEventLoop;

/**
 * Class HostResolver.
 *
 * Resolves the host for a Memcached cluster node.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class HostResolver implements HostResolverInterface
{
    public function __construct(
        private readonly ?ResolverInterface $dnsResolver
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolveOptimalHost(ClusterNodeInterface $clusterNode): string
    {
        return $clusterNode->getPrivateIp() ?? (
            $this->dnsResolver !== null ?
                TasqueEventLoop::await($this->dnsResolver->resolve($clusterNode->getHost())) :
                $clusterNode->getHost()
        );
    }
}
