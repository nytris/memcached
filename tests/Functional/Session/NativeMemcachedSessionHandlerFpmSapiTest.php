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

namespace Nytris\Memcached\Tests\Functional\Session;

use Asmblah\FastCgi\FastCgi;
use Asmblah\FastCgi\Launcher\PhpFpmLauncher;
use Asmblah\FastCgi\Session\SessionInterface;
use Nytris\Memcached\Tests\AbstractTestCase;

/**
 * Class NativeMemcachedSessionHandlerFpmSapiTest.
 *
 * Tests NativeMemcachedSessionHandler with a real php-fpm SAPI session stored in Memcached.
 * Auto-discovery is simulated, see Harness/Fixtures/www/use_session.php.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class NativeMemcachedSessionHandlerFpmSapiTest extends AbstractTestCase
{
    private string $baseDir;
    private string $configFilePath;
    private FastCgi $fastCgi;
    private string $phpFpmBinaryPath;
    private SessionInterface $session;
    private string $socketPath;
    private string $wwwDir;

    public function setUp(): void
    {
        $this->baseDir = dirname(__DIR__, 3);
        $this->wwwDir = 'tests/Functional/Harness/Fixtures/www';
        $this->phpFpmBinaryPath = dirname(PHP_BINARY, 2) . '/sbin/php-fpm';

        $dataDir = $this->baseDir . '/var/test';
        @mkdir($dataDir, 0777, true);
        $this->socketPath = $dataDir . '/php-fpm.test.sock';
        $logFilePath = $dataDir . '/php-fpm.log';

        $this->configFilePath = $dataDir . '/php-fpm.conf';
        file_put_contents($this->configFilePath, <<<CONFIG
[global]
error_log = $logFilePath

[www]
listen = $this->socketPath
pm = static
pm.max_children = 1

CONFIG
        );

        $this->fastCgi = new FastCgi(
            baseDir: $this->baseDir,
            wwwDir: $this->wwwDir,
            socketPath: $this->socketPath,
            launcher: new PhpFpmLauncher(
                $this->phpFpmBinaryPath,
                $this->configFilePath
            )
        );
    }

    public function tearDown(): void
    {
        $this->session->quit();
    }

    public function testSessionHandlerSupportsDynamicMode(): void
    {
        $this->session = $this->fastCgi->start();

        $response1 = $this->session->sendGetRequest(
            'use_session.php',
            '/path/to/my-page',
            ['clear' => 'yes']
        );
        $response2 = $this->session->sendGetRequest(
            'use_session.php',
            '/path/to/my-page',
            []
        );
        $response3 = $this->session->sendGetRequest(
            'use_session.php',
            '/path/to/my-page',
            []
        );

        static::assertSame('Session cleared', $response1->getBody());
        static::assertSame('Not loaded from session', $response2->getBody());
        static::assertSame('Loaded from session: my data', $response3->getBody());
    }
}
