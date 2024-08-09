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

namespace Nytris\Memcached\Library;

use Asmblah\PhpCodeShift\CodeShiftInterface;
use Asmblah\PhpCodeShift\Shifter\Filter\FileFilterInterface;
use Asmblah\PhpCodeShift\Shifter\Shift\Shift\ClassHook\ClassHookShiftSpec;
use LogicException;
use Memcached;
use Nytris\Memcached\Cluster\ClusterConfigClientInterface;
use Nytris\Memcached\Environment\EnvironmentInterface;
use Nytris\Memcached\Memcached\MemcachedHook;
use Nytris\Memcached\Session\SavePathProcessorInterface;

/**
 * Class Library.
 *
 * Encapsulates an installation of the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Library implements LibraryInterface
{
    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly ClusterConfigClientInterface $clusterConfigClient,
        private readonly SavePathProcessorInterface $sessionSavePathProcessor,
        CodeShiftInterface $codeShift,
        FileFilterInterface $memcachedClassHookFilter
    ) {
        if (!$environment->isExtensionLoaded('memcached')) {
            throw new LogicException('ext-memcached PHP extension is not installed');
        }

        $codeShift->shift(
            new ClassHookShiftSpec(
                Memcached::class,
                MemcachedHook::class
            ),
            $memcachedClassHookFilter
        );
    }

    /**
     * @inheritDoc
     */
    public function getClusterConfigClient(): ClusterConfigClientInterface
    {
        return $this->clusterConfigClient;
    }

    /**
     * @inheritDoc
     */
    public function getEnvironment(): EnvironmentInterface
    {
        return $this->environment;
    }

    /**
     * @inheritDoc
     */
    public function processSessionSavePath(string $savePath): string
    {
        return $this->sessionSavePathProcessor->processSessionSavePath($savePath);
    }

    /**
     * @inheritDoc
     */
    public function uninstall(): void
    {
    }
}
