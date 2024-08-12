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

namespace Nytris\Memcached\Tests\Unit\Environment;

use Nytris\Memcached\Environment\Environment;
use Nytris\Memcached\Tests\AbstractTestCase;

/**
 * Class EnvironmentTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class EnvironmentTest extends AbstractTestCase
{
    private Environment $environment;

    public function setUp(): void
    {
        $this->environment = new Environment();
    }

    public function testGetIniSettingFetchesTheSetting(): void
    {
        ini_set('session.save_path', 'test');

        static::assertSame('test', $this->environment->getIniSetting('session.save_path'));
    }

    public function testIsExtensionLoadedCorrectlyDetermines(): void
    {
        static::assertTrue($this->environment->isExtensionLoaded('core'));
        static::assertFalse($this->environment->isExtensionLoaded('probablynotvalid'));
    }

    public function testSetIniSettingSetsTheSetting(): void
    {
        $this->environment->setIniSetting('session.save_path', 'test2');

        static::assertSame('test2', ini_get('session.save_path'));
    }
}
