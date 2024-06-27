#!/usr/bin/env php
<?php

! defined('DEFAULT_TIMEZONE') && define('DEFAULT_TIMEZONE', 'UTC');

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
date_default_timezone_set(DEFAULT_TIMEZONE);

return match ($argv[1] ?? '') {
    'mongo:stream-listener' => Medeirosinacio\MongoTtlIndexChangeStream\MongoStreamListenerCommand::listen(),
    default => throw new InvalidArgumentException('Invalid command'),
};
