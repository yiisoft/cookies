<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // Virtual "provide"-only package (fulfilled by httpsoft/http-message), never referenced by class name.
    ->ignoreErrorsOnPackage('psr/http-message-implementation', [ErrorType::UNUSED_DEPENDENCY])
    // Optional dependency: only referenced via nullable type hints, not required at runtime.
    ->ignoreErrorsOnPackage('psr/clock', [ErrorType::DEV_DEPENDENCY_IN_PROD]);
