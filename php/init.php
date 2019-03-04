<?php
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/include.php';

use Google\Cloud\Spanner\Database;

startTrace();

spannerDbContext(function(Database $db) {
    $operation = $db->create(['statements' => explode(';', file_get_contents(__DIR__.'/init.ddl'))]);
    $operation->pollUntilComplete();
    echo 'finished db init'.PHP_EOL;
});