<?php

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;

return RectorConfig::configure()
    ->withRules(
        [
            NewMethodCallWithoutParenthesesRector::class,
            AddTypeToConstRector::class,
        ]
    );
