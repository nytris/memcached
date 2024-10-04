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

namespace Nytris\Memcached\Cluster;

use Nytris\Memcached\Exception\InvalidClusterConfigResponseException;

/**
 * Class ClusterConfigParserInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ClusterConfigParser implements ClusterConfigParserInterface
{
    /**
     * @inheritDoc
     */
    public function parseClusterConfigResponse(string $clusterConfigResponse): ClusterConfigInterface
    {
        if (
            preg_match(
                '/^CONFIG cluster \d+ \d+\r\n(?<configVersion>\d+)\n(?<nodes>(?:([^|]+)\|([^|]*)\|\d++(?: |\n$))+)$/',
                $clusterConfigResponse,
                $matches
            ) === 0
        ) {
            throw new InvalidClusterConfigResponseException(
                sprintf(
                    'Unexpected "config get cluster" command response: "%s"',
                    $clusterConfigResponse
                )
            );
        }

        ['nodes' => $nodeList, 'configVersion' => $configVersion] = $matches;

        /** @var ClusterNodeInterface[] $nodes */
        $nodes = [];

        foreach (explode(' ', $nodeList) as $node) {
            if (
                preg_match(
                    '/^(?<host>[^|]+)\|(?<privateIp>[^|]*)\|(?<port>\d+)$/',
                    $node,
                    $matches
                ) === 0
            ) {
                // This should not be reachable as the first regex should validate all.
                throw new InvalidClusterConfigResponseException(
                    sprintf(
                        'Failed parsing "config get cluster" command response nodes: "%s"',
                        $nodeList
                    )
                );
            }

            ['host' => $host, 'privateIp' => $privateIp, 'port' => $port] = $matches;

            $nodes[] = new ClusterNode(
                host: $host,
                privateIp: $privateIp === '' ? null : $privateIp,
                port: (int) $port
            );
        }

        return new ClusterConfig((int) $configVersion, $nodes);
    }
}
