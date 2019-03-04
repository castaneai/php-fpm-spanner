<?php
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/include.php';

use Google\Cloud\Spanner\Database;
use Google\Cloud\Spanner\Transaction;

// startTrace();

spannerDbContext(function(Database $db) {
    printf('a');
    $db->runTransaction(function(Transaction $tx) {
        $userId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $tx->executeUpdate('INSERT INTO User (userId, name) VALUES(@userId, @name)', ['parameters' => [
            'userId' => $userId,
            'name' => 'name-of-'.$userId,
        ]]);

        sleep(1);

        spannerDbContext(function(Database $db2) use ($userId) {
            $db2->runTransaction(function(Transaction $tx2) use ($userId) {
                $cnt = $tx2->execute('SELECT COUNT(*) FROM User WHERE userId = @userId', ['parameters' => [
                    'userId' => $userId,
                ]])->rows()->current()[0];
                printf('[inner tx] user count: %d'.PHP_EOL, $cnt);
                $innerTxCommittedAt = $tx2->commit();
                printf('inner tx committed: %s'.PHP_EOL, $innerTxCommittedAt->formatAsString());
            });
        });

        $outerTxCommittedAt = $tx->commit();
        printf('outer tx committed: %s'.PHP_EOL, $outerTxCommittedAt->formatAsString());
    });
});

