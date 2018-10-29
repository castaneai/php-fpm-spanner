<?php
require __DIR__.'/vendor/autoload.php';

use Google\Cloud\Spanner\SpannerClient;

$instanceId = getenv('SPANNER_INSTANCE_ID');
$databaseId = getenv('SPANNER_DATABASE_ID');

$spanner = new SpannerClient();
$db = $spanner->connect($instanceId, $databaseId);

$row = $db->execute('SELECT "Hello, world"')->rows()->current();
var_dump($row);
