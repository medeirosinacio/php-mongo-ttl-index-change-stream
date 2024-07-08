<?php

declare(strict_types=1);

namespace Medeirosinacio\MongoTtlIndexChangeStream\Factories;

use MongoDB\Client;

final class MongoFactory
{
    public static function make(): Client
    {
        return new Client(
            'mongodb://mongo:27017',
            [
                'username' => 'root',
                'password' => 'root',
                'ssl' => false,
                'replicaSet' => 'rs0',
            ]
        );
    }
}
