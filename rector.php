<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\AddInstanceofAssertForNullableArgumentRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\AddInstanceofAssertForNullableInstanceRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ])
    ->withComposerBased(phpunit: true)
    ->withImportNames(importShortClasses: false)
    // Small code base: run in one process so errors are reported with the file that caused them.
    ->withoutParallel()
    ->withSkip([
        // PHPUnit calls setUp() and reads fixtures through the parent class; these rules break tests.
        PrivatizeFinalClassMethodRector::class => [__DIR__.'/tests'],
        RemoveUnusedPrivateMethodRector::class => [__DIR__.'/tests'],
        ReadOnlyPropertyRector::class => [__DIR__.'/tests'],
        AddInstanceofAssertForNullableArgumentRector::class => [__DIR__.'/tests'],
        AddInstanceofAssertForNullableInstanceRector::class => [__DIR__.'/tests'],
        // Symfony < 7 cannot dump closure factories; keep the [class, method] form.
        ArrayToFirstClassCallableRector::class => [__DIR__.'/src/Symfony'],
        // Exact float comparisons are intended in these tests.
        AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector::class => [__DIR__.'/tests'],
    ]);
