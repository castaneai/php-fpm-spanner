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
    opencensus_trace_method(\Google\Cloud\Spanner\Database::class, 'createSession');
    opencensus_trace_method(\Google\Cloud\Spanner\Database::class, 'runTransaction');

    // $exporter = new \OpenCensus\Trace\Exporter\OneLineEchoExporter();
    $exporter = new \OpenCensus\Trace\Exporter\StackdriverExporter();
    \OpenCensus\Trace\Tracer::start($exporter);
}

function spannerClientContext(Closure $func, CacheItemPoolInterface $authCache = null)
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

    return $func($spanner, $instanceId, $databaseId);
}

function spannerDbContext(Closure $func, CacheItemPoolInterface $authCache = null, SessionPoolInterface $sessionPool = null)
{
    return spannerClientContext(function(SpannerClient $client, $instanceId, $databaseId) use ($sessionPool, $func) {
        if ($sessionPool === null) {
            $sessionCacheItemPool = new SysVCacheItemPool();
            $sessionPool = new CacheSessionPool($sessionCacheItemPool, [
                'minSession' => 10,
            ]);
        }
        $databaseOptions = [
            'sessionPool' => $sessionPool,
        ];
        $db = $client->connect($instanceId, $databaseId, $databaseOptions);
        return $func($db);
    }, $authCache);
}
