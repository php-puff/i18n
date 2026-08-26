<?php

/*
 * PHP Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n\Loader;

interface LoaderInterface
{
    public function resolve(string $locale): ?string;

    /** @return array<string, mixed> */
    public function load(string $locale, bool $reload = false): array;

    public function flush(): bool;
}
