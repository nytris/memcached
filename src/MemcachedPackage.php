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
use Nytris\Memcached\Library\ClientMode;

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
        private readonly FileFilterInterface $memcachedClassHookFilter = new FileFilter(
            '**/vendor/tedivm/stash/src/Stash/Driver/Sub/Memcached.php'
        )
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
