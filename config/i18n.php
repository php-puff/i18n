<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

return [
    'default' => 'en',
    'sources' => [
        \dirname(__DIR__) . '/i18n',
        \dirname(__DIR__) . '/app/I18n',
    ],
    // Set to null or an empty string to disable the translation file cache.
    'cache' => \dirname(__DIR__) . '/runtime/i18n',
    'query' => 'i18n',
    'cookie' => 'i18n',
];
