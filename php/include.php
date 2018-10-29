<?php
require __DIR__.'/vendor/autoload.php';

use Google\Cloud\Spanner\SpannerClient;
use Google\Cloud\Spanner\Session\SessionPoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Google\Cloud\Spanner\Session\CacheSessionPool;
use Google\Auth\Cache\SysVCacheItemPool;

function startTrace()
{
    opencensus_trace_method(\Google\Cloud\Core\GrpcRequestWrapper::class, 'send', function ($request, $args, $options) {
        return ['attributes' => [
            'methodName' => $args[1],
            'options' => json_encode($options),
        ]];
    });

    opencensus_trace_method(\Grpc\Call::class, 'startBatch', function($scope, $batch) {
        return ['attributes' => ['batch' => json_encode($batch)]];
    });

    \OpenCensus\Trace\Tracer::start(new \OpenCensus\Trace\Exporter\OneLineEchoExporter());
}

function spannerContext(Closure $func, CacheItemPoolInterface $authCache = null, SessionPoolInterface $sessionPool = null)
{
    $instanceId = getenv('SPANNER_INSTANCE_ID');
    $databaseId = getenv('SPANNER_DATABASE_ID');

    if ($authCache === null) {
        $authCache = new SysVCacheItemPool();
    }
    $spannerClientOptions = [
        'authCache' => $authCache,
    ];
    $spanner = new SpannerClient($spannerClientOptions);

    if ($sessionPool === null) {
        $sessionCacheItemPool = new SysVCacheItemPool();
        $sessionPool =  new CacheSessionPool($sessionCacheItemPool, [
            'minSession' => 10,
        ]);
    }
    $databaseOptions = [
        'sessionPool' => $sessionPool,
    ];
    $db = $spanner->connect($instanceId, $databaseId, $databaseOptions);
    return $func($db);
}
