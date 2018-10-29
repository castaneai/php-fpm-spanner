<?php
require __DIR__.'/vendor/autoload.php';

use Google\Cloud\Spanner\SpannerClient;
use Psr\Cache\CacheItemPoolInterface;

function startTrace()
{
    opencensus_trace_method(\Google\Cloud\Core\GrpcRequestWrapper::class, 'send', function ($request, $args, $options) {
        return ['attributes' => [
            'methodName' => $args[1],
            'options' => json_encode($options),
        ]];
    });

    opencensus_trace_method(\Grpc\Call::class, 'startBatch', function($scope, $args) {
        return ['attributes' => ['args' => json_encode($args)]];
    });

    \OpenCensus\Trace\Tracer::start(new \OpenCensus\Trace\Exporter\OneLineEchoExporter());
}

function spannerContext(Closure $func, CacheItemPoolInterface $authCache = null, CacheItemPoolInterface $sessionPoolCache = null)
{
    $instanceId = getenv('SPANNER_INSTANCE_ID');
    $databaseId = getenv('SPANNER_DATABASE_ID');
    $spanner = new SpannerClient();
    $db = $spanner->connect($instanceId, $databaseId);
    return $func($db);
}
