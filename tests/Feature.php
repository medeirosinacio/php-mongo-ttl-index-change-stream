<?php

use Medeirosinacio\SkeletonPhp\MongoStreamListener;

it('foo', function () {
    $example = new MongoStreamListener();

    $result = $example->foo();

    expect($result)->toBe('bar');
});
