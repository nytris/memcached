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

use Nytris\Memcached\Cluster\ClusterConfigClientInterface;

/**
 * Class SavePathProcessor.
 *
 * Handles auto-discovery of AWS Memcached ElastiCache nodes for the session connection.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SavePathProcessor implements SavePathProcessorInterface
{
    public function __construct(
        private readonly ClusterConfigClientInterface $clusterConfigClient
    ) {
    }

    /**
     * @inheritDoc
     */
    public function processSessionSavePath(string $savePath): string
    {
        if (preg_match('/^(?<host>[^:]+):(?<port>\d+)$/', $savePath, $matches) === 0) {
            // Save path is not a single host, so ignore auto-discovery.
            return $savePath;
        }

        // Otherwise perform auto-discovery if applicable.
        $clusterConfig = $this->clusterConfigClient->getClusterConfig($matches['host'], (int)$matches['port']);

        /** @var string[] $processedSavePathHosts */
        $processedSavePathHosts = [];

        foreach ($clusterConfig->getNodes() as $clusterNode) {
            $processedSavePathHosts[] = $clusterNode->getHost() . ':' . $clusterNode->getPort();
        }

        // Add all auto-discovered Memcached cluster nodes to the session save path.
        return implode(',', $processedSavePathHosts);
    }
}
