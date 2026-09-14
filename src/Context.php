<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n;

final class Context
{
    private string $locale;

    private readonly string $default;

    public function __construct(string $locale)
    {
        $this->locale = $this->default = Locale::normalize($locale);
    }

    public function get(): string
    {
        return $this->locale;
    }

    public function set(string $locale): void
    {
        $this->locale = Locale::normalize($locale);
    }

    public function default(): string
    {
        return $this->default;
    }
}
