<?php

declare(strict_types=1);

namespace Medeirosinacio\MongoTtlIndexChangeStream;

use DateInterval;
use Exception;
use Psr\SimpleCache\CacheInterface;

final class LocalCacheDriver implements CacheInterface
{
    private readonly string $cacheDir;

    public function __construct(string $cacheDir = '/app/runtime/cache')
    {
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR);
        if (is_dir($this->cacheDir)) {
            return;
        }
        if (mkdir($this->cacheDir, 0755, true)) {
            return;
        }
        if (is_dir($this->cacheDir)) {
            return;
        }
        throw new Exception('Failed to create cache directory.');
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = 3600): bool
    {
        $filePath = $this->getFilePath($key);
        $data = [
            'expires_at' => time() + $ttl,
            'value' => serialize($value),
        ];

        return (bool) file_put_contents($filePath, serialize($data));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return null;
        }

        $data = unserialize(file_get_contents($filePath));

        if ($data['expires_at'] < time()) {
            unlink($filePath);

            return null;
        }

        return unserialize($data['value']);
    }

    public function delete(string $key): bool
    {
        $filePath = $this->getFilePath($key);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir.'/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    private function getFilePath(string $key): string
    {
        return $this->cacheDir.DIRECTORY_SEPARATOR.md5($key).'.cache';
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->get($key);
        }

        return $items;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, is_int($ttl) ? $ttl : 3600);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
}
