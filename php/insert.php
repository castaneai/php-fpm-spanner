<?php
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/include.php';

use Google\Cloud\Spanner\Database;
use Google\Cloud\Spanner\Transaction;

startTrace();

spannerDbContext(function(Database $db) {
    $start = microtime(true);
    $db->runTransaction(function(Transaction $tx) {
        $userId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $tx->executeUpdate('INSERT INTO User (userId, name) VALUES(@userId, @name)', ['parameters' => [
            'userId' => $userId,
            'name' => 'name-of-'.$userId,
        ]]);
        $tx->commit();
    });
    printf('Insert Tx time: %.2f ms'.PHP_EOL, (microtime(true) - $start) * 1000);
});