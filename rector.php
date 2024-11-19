<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
    ])->withSets([
        LevelSetList::UP_TO_PHP_83,
    ])
    ->withCache('./.rector-cache', FileCacheStorage::class);
