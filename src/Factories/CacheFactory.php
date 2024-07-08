<?php

declare(strict_types=1);

namespace Medeirosinacio\MongoTtlIndexChangeStream\Factories;

use InvalidArgumentException;
use Medeirosinacio\MongoTtlIndexChangeStream\LocalCacheDriver;
use Psr\SimpleCache\CacheInterface;

final class CacheFactory
{
    public static function make(string $driver = 'local'): CacheInterface
    {
        return match ($driver) {
            'local' => new LocalCacheDriver(),
            default => throw new InvalidArgumentException('Invalid cache driver'),
        };
    }
}
