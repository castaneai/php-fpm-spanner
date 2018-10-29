<?php
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/include.php';

use Google\Cloud\Spanner\Database;

startTrace();

spannerContext(function(Database $db) {
    $start = microtime(true);
    $row = $db->execute('SELECT "Hello, world"')->rows()->current();
    printf('Query time: %.2f ms'.PHP_EOL, (microtime(true) - $start) * 1000);
});
