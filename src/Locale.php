<?php

/*
 * PHP Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n;

final class Locale
{
    public static function normalize(string $locale): string
    {
        $parts = \explode('-', \str_replace('_', '-', \trim($locale)));
        if (\preg_match('/^[a-z]{2,3}$/iD', $parts[0]) !== 1) {
            throw new \InvalidArgumentException("Invalid locale [{$locale}].");
        }

        $normalized = [\strtolower($parts[0])];
        foreach (\array_slice($parts, 1) as $part) {
            if (\preg_match('/^[a-z0-9]{2,8}$/iD', $part) !== 1) {
                throw new \InvalidArgumentException("Invalid locale [{$locale}].");
            }
            $normalized[] = match (true) {
                \strlen($part) === 2 && \ctype_alpha($part) => \strtoupper($part),
                \strlen($part) === 4 && \ctype_alpha($part) => \ucfirst(\strtolower($part)),
                default => \strtolower($part),
            };
        }

        return \implode('-', $normalized);
    }

    public static function tryNormalize(string $locale): ?string
    {
        try {
            return self::normalize($locale);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /** @return non-empty-list<string> */
    public static function fallbacks(string $locale): array
    {
        $parts = \explode('-', self::normalize($locale));
        $fallbacks = [];
        while ($parts !== []) {
            $fallbacks[] = \implode('-', $parts);
            \array_pop($parts);
        }
        return $fallbacks;
    }
}
