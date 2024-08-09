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

use Nytris\Memcached\Exception\InvalidIniSettingException;
use Nytris\Memcached\Memcached;
use SessionHandler;

/**
 * Class NativeMemcachedSessionHandler.
 *
 * PHP session handler that wraps the memcached session save handler provided by ext-memcached,
 * adding support for AWS Memcached ElastiCache auto-discovery.
 *
 * Symfony's own MemcachedSessionHandler doesn't support session locking,
 * whereas using the native session save handler does.
 *
 * Based on https://github.com/zikula/NativeSession.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class NativeMemcachedSessionHandler extends SessionHandler
{
    /**
     * @param string|null $savePath Comma separated list of servers: e.g. `mc1.example.com:11211,mc2.example.com:11211`.
     * @param array<mixed> $options session configuration options.
     */
    public function __construct(
        ?string $savePath = '127.0.0.1:11211',
        private readonly array $options = []
    ) {
        $environment = Memcached::getEnvironment();

        // Use INI setting if null given.
        $savePath ??= $environment->getIniSetting('session.save_path');

        // Handle auto-discovery of AWS Memcached ElastiCache nodes.
        $savePath = Memcached::processSessionSavePath($savePath);

        $environment->setIniSetting('session.save_handler', 'memcached');
        $environment->setIniSetting('session.save_path', $savePath);

        /**
         * Set INI settings from options.
         *
         * @see https://github.com/php-memcached-dev/php-memcached/blob/master/memcached.ini
         */
        $validOptions = array_flip([
            'memcached.compression_factor',
            'memcached.compression_threshold',
            'memcached.compression_type',
            'memcached.serializer',
            'memcached.sess_lock_wait',
            'memcached.sess_locking',
            'memcached.sess_prefix',
        ]);

        foreach ($this->options as $name => $value) {
            if (!array_key_exists($name, $validOptions)) {
                throw new InvalidIniSettingException(sprintf('INI setting "%s" is invalid', $name));
            }

            $environment->setIniSetting($name, $value);
        }
    }
}
