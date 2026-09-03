<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withCache(
        cacheDirectory: __DIR__ . '/.rector-cache',
        cacheClass: FileCacheStorage::class,
    )
    // Library supports PHP >=8.2, so Rector must not introduce newer syntax
    ->withPhpSets(php82: true)
    ->withPreparedSets(
        if: true,
        deadCode: true,
        instanceOf: true,
        codeQuality: true,
        codingStyle: true,
        earlyReturn: true,
        rectorPreset: true,
        privatization: true,
        typeDeclarations: true,
        phpunitMockToStub: true,
        phpunitCodeQuality: true,
        phpunitNarrowAsserts: true,
    )
    ->withSkip([
        Rector\CodingStyle\Rector\String_\SimplifyQuoteEscapeRector::class,
        Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector::class,
        // In data providers a named null arg declares the tested scenario, not a setting
        Rector\DeadCode\Rector\MethodCall\RemoveNullNamedArgOnNullDefaultParamRector::class => [
            __DIR__ . '/tests/UseCase/Invoice/InvoicesTest.php',
        ],
    ]);
