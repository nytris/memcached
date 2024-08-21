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

use Asmblah\PhpCodeShift\CodeShift;
use Asmblah\PhpCodeShift\Shifter\Filter\FileFilter;
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Memcached\Cluster\ClusterConfigClientInterface;
use Nytris\Memcached\Cluster\ClusterConfigInterface;
use Nytris\Memcached\Cluster\ClusterNodeInterface;
use Nytris\Memcached\Environment\Environment;
use Nytris\Memcached\Library\ClientMode;
use Nytris\Memcached\Library\Library;
use Nytris\Memcached\Memcached;
use Nytris\Memcached\MemcachedPackageInterface;
use Nytris\Memcached\Session\NativeMemcachedSessionHandler;
use Nytris\Memcached\Session\SavePathProcessor;
use Tasque\Core\Scheduler\ContextSwitch\ManualStrategy;
use Tasque\EventLoop\ContextSwitch\FutureTickScheduler;
use Tasque\EventLoop\TasqueEventLoop;
use Tasque\EventLoop\TasqueEventLoopPackageInterface;
use Tasque\Tasque;
use Tasque\TasquePackageInterface;

require dirname(__DIR__, 5) . '/vendor/autoload.php';

$memcachedClassHookFilter = new FileFilter(dirname(__DIR__, 2) . '/**');

Tasque::install(
    mock(PackageContextInterface::class),
    mock(TasquePackageInterface::class, [
        'getSchedulerStrategy' => new ManualStrategy(),
        'isPreemptive' => false,
    ])
);
TasqueEventLoop::install(
    mock(PackageContextInterface::class),
    mock(TasqueEventLoopPackageInterface::class, [
        'getContextSwitchInterval' => TasqueEventLoopPackageInterface::DEFAULT_CONTEXT_SWITCH_INTERVAL,
        // Switch every tick to make tests deterministic.
        'getContextSwitchScheduler' => new FutureTickScheduler(),
        'getEventLoop' => null,
    ])
);
Memcached::install(
    mock(PackageContextInterface::class),
    mock(MemcachedPackageInterface::class, [
        'getClientMode' => ClientMode::DYNAMIC,
        'getMemcachedClassHookFilter' => $memcachedClassHookFilter,
    ])
);

$clusterConfigClient = mock(ClusterConfigClientInterface::class);
$library = new Library(
    new Environment(),
    $clusterConfigClient,
    new SavePathProcessor($clusterConfigClient),
    new CodeShift(),
    memcachedClassHookFilter: $memcachedClassHookFilter
);
Memcached::setLibrary($library);

$clusterConfigClient->allows()
    ->getClusterConfig('my.elasticache.config.endpoint', 12345)
    ->andReturn(mock(ClusterConfigInterface::class, [
        'getNodes' => [
            mock(ClusterNodeInterface::class, [
                'getHost' => '127.0.0.1',
                'getPort' => 11211,
            ]),
        ],
    ]));

// Use a fake ElastiCache endpoint, which will have "auto-discovery" performed on it
// by the stub above, repointing back to the test Memcached instance.
session_set_save_handler(new NativeMemcachedSessionHandler('my.elasticache.config.endpoint:12345'));

session_id('my_session');
session_start();

if (($_GET['clear'] ?? 'no') === 'yes') {
    session_destroy();
    print 'Session cleared';
    return;
}

$data = $_SESSION['my_data'] ?? null;

if ($data !== null) {
    print 'Loaded from session: ' . $data;
} else {
    print 'Not loaded from session';

    $_SESSION['my_data'] = 'my data';
}
