<?php

use Medeirosinacio\MongoTtlIndexChangeStream\LocalCacheDriver;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
    // Setup a temporary directory for cache
    $this->cacheDir = __DIR__.'/cache';
    if (!is_dir($this->cacheDir)) {
        mkdir($this->cacheDir, 0755, true);
    }
    $this->cacheDriver = new LocalCacheDriver($this->cacheDir);
});

afterEach(function () {
    // Cleanup the temporary cache directory
    $files = glob($this->cacheDir.'/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($this->cacheDir);
});

test('it can set and get a cache item', function () {
    $key = 'test_key';
    $value = 'test_value';

    assertTrue($this->cacheDriver->set($key, $value));
    assertSame($value, $this->cacheDriver->get($key));
});

test('it returns null for a non-existent cache item', function () {
    assertNull($this->cacheDriver->get('non_existent_key'));
});

test('it can delete a cache item', function () {
    $key = 'test_key';
    $value = 'test_value';

    $this->cacheDriver->set($key, $value);
    assertTrue($this->cacheDriver->delete($key));
    assertNull($this->cacheDriver->get($key));
});

test('it can clear all cache items', function () {
    $key1 = 'test_key1';
    $value1 = 'test_value1';
    $key2 = 'test_key2';
    $value2 = 'test_value2';

    $this->cacheDriver->set($key1, $value1);
    $this->cacheDriver->set($key2, $value2);
    assertTrue($this->cacheDriver->clear());
    assertNull($this->cacheDriver->get($key1));
    assertNull($this->cacheDriver->get($key2));
});

test('it can check if a cache item exists', function () {
    $key = 'test_key';
    $value = 'test_value';

    $this->cacheDriver->set($key, $value);
    assertTrue($this->cacheDriver->has($key));
    $this->cacheDriver->delete($key);
    assertFalse($this->cacheDriver->has($key));
});

test('it can set and get multiple cache items', function () {
    $items = [
        'key1' => 'value1',
        'key2' => 'value2',
    ];

    assertTrue($this->cacheDriver->setMultiple($items));
    $retrievedItems = $this->cacheDriver->getMultiple(array_keys($items));
    assertSame($items, $retrievedItems);
});

test('it can delete multiple cache items', function () {
    $items = [
        'key1' => 'value1',
        'key2' => 'value2',
    ];

    $this->cacheDriver->setMultiple($items);
    assertTrue($this->cacheDriver->deleteMultiple(array_keys($items)));
    foreach ($items as $key => $value) {
        assertNull($this->cacheDriver->get($key));
    }
});
