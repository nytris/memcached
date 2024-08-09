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

use Nytris\Memcached\Exception\UnsupportedCommandException;
use Nytris\Memcached\Library\ClientMode;
use Nytris\Memcached\Memcached\IoInterface;
use React\Socket\ConnectorInterface;
use RuntimeException;
use Tasque\EventLoop\TasqueEventLoop;

/**
 * Class ClusterConfigClient.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ClusterConfigClient implements ClusterConfigClientInterface
{
    public function __construct(
        private readonly ClientMode $clientMode,
        private readonly ConnectorInterface $connector,
        private readonly IoInterface $io,
        private readonly ClusterConfigParserInterface $clusterConfigParser
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getClusterConfig(string $host, int $port): ClusterConfigInterface
    {
        if ($this->clientMode === ClientMode::STATIC) {
            // In static client mode, just treat the given node as the only one and do not attempt auto-discovery.

            return new ClusterConfig(
                version: null,
                nodes: [
                    new ClusterNode(host: $host, privateIp: null, port: $port)
                ]
            );
        }

        $connection = TasqueEventLoop::await($this->connector->connect('tcp://' . $host . ':' . $port));

        $versionResponse = $this->io->sendCommand($connection, 'version');

        if (preg_match('/^VERSION\s/', $versionResponse) === 0) {
            throw new RuntimeException(sprintf('Unexpected "version" command response: "%s"', $versionResponse));
        }

        try {
            $clusterConfigResponse = $this->io->sendCommand($connection, 'config get cluster', multiLineResponse: true);
        } catch (UnsupportedCommandException) {
            // Must be a non-AWS ElastiCache Memcached instance, fall back to just using the given server.
            return new ClusterConfig(
                version: null,
                nodes: [
                    new ClusterNode(host: $host, privateIp: null, port: $port)
                ]
            );
        }

        $connection->close();

        return $this->clusterConfigParser->parseClusterConfigResponse($clusterConfigResponse);
    }
}
