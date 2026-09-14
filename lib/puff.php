<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

use Puff\Di\Container;
use Puff\I18n\I18n;

if (!\function_exists('lang')) {
    function lang(mixed ...$arguments): mixed
    {
        $container = Container::getInstance();
        if ($container === null) {
            throw new LogicException('The Puff container has not been initialized.');
        }
        $translator = $container->get(I18n::class);
        return $arguments === [] ? $translator : $translator->get(...$arguments);
    }
}

if (!\function_exists('i18n')) {
    function i18n(?string $locale = null): string|I18n
    {
        $translator = lang();
        if (!$translator instanceof I18n) {
            throw new LogicException('The i18n service is not registered.');
        }
        return $translator->locale($locale);
    }
}
