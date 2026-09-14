<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

use Puff\Di\Facade;

/**
 * @method static bool                   has(string $key)
 * @method static string                 get(string $key, mixed ...$args)
 * @method static \Puff\I18n\I18n        add(string|array<string, mixed> $key, mixed $value = null)
 * @method static \Puff\I18n\I18n        set(string|array<string, mixed> $key, mixed $value = null)
 * @method static mixed                  raw(?string $key = null, mixed $default = [])
 * @method static array<string, mixed>   all()
 * @method static string|\Puff\I18n\I18n i18n(?string $locale = null)
 * @method static string|\Puff\I18n\I18n locale(?string $locale = null)
 * @method static \Puff\I18n\I18n        setLocale(string $locale)
 * @method static string                 getLocale()
 * @method static bool                   flush()
 */
class I18n extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'i18n';
    }
}
