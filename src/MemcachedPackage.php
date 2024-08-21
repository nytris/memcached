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

namespace Nytris\Memcached;

use Asmblah\PhpCodeShift\Shifter\Filter\FileFilter;
use Asmblah\PhpCodeShift\Shifter\Filter\FileFilterInterface;
use Asmblah\PhpCodeShift\Shifter\Filter\MultipleFilter;
use Closure;
use Nytris\Memcached\Library\ClientMode;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;

/**
 * Class MemcachedPackage.
 *
 * Configures the installation of Nytris Memcached.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class MemcachedPackage implements MemcachedPackageInterface
{
    public function __construct(
        private readonly ClientMode $clientMode = ClientMode::STATIC,
        private readonly FileFilterInterface $memcachedClassHookFilter = new MultipleFilter([
            new FileFilter(
                '**/vendor/symfony/cache/Adapter/MemcachedAdapter.php'
            ),
            new FileFilter(
                '**/vendor/tedivm/stash/src/Stash/Driver/Sub/Memcached.php'
            ),
        ]),
        /**
         * @var Closure(string): ConnectorInterface
         */
        private readonly ?Closure $connectorFactory = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getClientMode(): ClientMode
    {
        return $this->clientMode;
    }

    /**
     * @inheritDoc
     */
    public function getConnector(string $packageCachePath): ConnectorInterface
    {
        return $this->connectorFactory !== null ?
            ($this->connectorFactory)($packageCachePath) :
            new Connector([
                'dns' => true,
                'happy_eyeballs' => false,
            ]);
    }

    /**
     * @inheritDoc
     */
    public function getMemcachedClassHookFilter(): FileFilterInterface
    {
        return $this->memcachedClassHookFilter;
    }

    /**
     * @inheritDoc
     */
    public function getPackageFacadeFqcn(): string
    {
        return Memcached::class;
    }
}
