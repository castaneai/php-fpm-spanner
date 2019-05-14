<?php
require __DIR__.'/vendor/autoload.php';

use Google\Cloud\Spanner\SpannerClient;
use Google\Cloud\Spanner\Session\SessionPoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Google\Cloud\Spanner\Session\CacheSessionPool;
use Google\Auth\Cache\SysVCacheItemPool;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

function startTrace()
{
    opencensus_trace_method(\Google\Cloud\Core\GrpcRequestWrapper::class, 'send', function ($request, $args, $options) {
        return ['attributes' => [
            'methodName' => $args[1],
            'options' => json_encode($options),
        ]];
    });
    opencensus_trace_method(\Google\ApiCore\Transport\GrpcTransport::class, '__construct', function ($baseStub, $hostname, $opts, $channel) {
        return [
            'attributes' => [
                'backtrace' => json_encode(array_map(function($t) { return "${t['file']}:${t['line']}"; }, debug_backtrace())),
                'hostname' => $hostname,
                'opts' => json_encode($opts),
                'channel' => json_encode($channel),
            ],
        ];
    });
    opencensus_trace_method(\Google\Cloud\Spanner\Database::class, 'execute', function($db, $sql) {
        return [
            'attributes' => [
                'sql' => $sql,
            ],
        ];
    });

    opencensus_trace_method(\Grpc\Call::class, 'startBatch', function($call, $batch) {
        return [
            'attributes' => [
                'batch' => json_encode($batch),
            ],
        ];
    });
    opencensus_trace_method(\Google\Cloud\Spanner\SpannerClient::class, '__construct');
    opencensus_trace_method(\Google\Cloud\Spanner\Database::class, 'selectSession');
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
            $sessionCacheItemPool = new FilesystemAdapter('spanner-session', 0, __DIR__.'/cache');
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
