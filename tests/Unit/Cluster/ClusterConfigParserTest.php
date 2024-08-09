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

namespace Nytris\Memcached\Tests\Unit\Cluster;

use Nytris\Memcached\Cluster\ClusterConfigParser;
use Nytris\Memcached\Exception\InvalidClusterConfigResponseException;
use Nytris\Memcached\Tests\AbstractTestCase;

/**
 * Class ClusterConfigParserTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ClusterConfigParserTest extends AbstractTestCase
{
    private ClusterConfigParser $parser;

    public function setUp(): void
    {
        $this->parser = new ClusterConfigParser();
    }

    public function testParseClusterConfigResponseCorrectlyParsesValidResponse(): void
    {
        $response = "CONFIG cluster 0 136\r\n" .
            "12\n" .
            "myCluster.pc4ldq.0001.use1.cache.amazonaws.com|10.82.235.120|11211 myCluster.pc4ldq.0002.use1.cache.amazonaws.com|10.80.249.27|11218\n";

        $config = $this->parser->parseClusterConfigResponse($response);

        static::assertSame(12, $config->getVersion());
        $nodes = $config->getNodes();
        static::assertCount(2, $nodes);
        static::assertSame('myCluster.pc4ldq.0001.use1.cache.amazonaws.com', $nodes[0]->getHost());
        static::assertSame('10.82.235.120', $nodes[0]->getPrivateIp());
        static::assertSame(11211, $nodes[0]->getPort());
        static::assertSame('myCluster.pc4ldq.0002.use1.cache.amazonaws.com', $nodes[1]->getHost());
        static::assertSame('10.80.249.27', $nodes[1]->getPrivateIp());
        static::assertSame(11218, $nodes[1]->getPort());
    }

    public function testParseClusterConfigResponseHandlesMissingPrivateIp(): void
    {
        $response = "CONFIG cluster 0 136\r\n" .
            "12\n" .
            "myCluster.pc4ldq.0001.use1.cache.amazonaws.com||11211 myCluster.pc4ldq.0002.use1.cache.amazonaws.com|10.80.249.27|11218\n";

        $config = $this->parser->parseClusterConfigResponse($response);

        static::assertSame(12, $config->getVersion());
        $nodes = $config->getNodes();
        static::assertCount(2, $nodes);
        static::assertSame('myCluster.pc4ldq.0001.use1.cache.amazonaws.com', $nodes[0]->getHost());
        static::assertNull($nodes[0]->getPrivateIp());
    }

    public function testParseClusterConfigResponseThrowsWhenResponseHeaderIsInvalid(): void
    {
        $response = "NOTVALID cluster 0 136\r\n" .
            "12\n" .
            "myCluster.pc4ldq.0001.use1.cache.amazonaws.com||11211 myCluster.pc4ldq.0002.use1.cache.amazonaws.com|10.80.249.27|11218\n";

        $this->expectException(InvalidClusterConfigResponseException::class);
        $this->expectExceptionMessage('Unexpected "config get cluster" command response: "NOTVALID cluster 0 136');

        $this->parser->parseClusterConfigResponse($response);
    }
}
