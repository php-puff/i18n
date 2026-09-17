<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n\Loader;

use Puff\I18n\Cache;
use Puff\I18n\Locale;

final readonly class IniLoader implements LoaderInterface
{
    /** @param list<string> $sources */
    public function __construct(
        private array $sources,
        private ?Cache $cache = null,
        private bool $debug = false,
    ) {
    }

    public function resolve(string $locale): ?string
    {
        foreach (Locale::fallbacks($locale) as $candidate) {
            foreach ($this->sources as $source) {
                if (\is_file($this->file($source, $candidate))) {
                    return $candidate;
                }
            }
        }
        return null;
    }

    public function load(string $locale, bool $reload = false): array
    {
        $locale = $this->resolve($locale) ?? Locale::normalize($locale);
        if (!$reload && !$this->debug && $this->cache !== null) {
            $items = $this->cache->read($locale);
            if ($items !== null) {
                return $items;
            }
        }

        $items = [];
        foreach ($this->sources as $source) {
            $file = $this->file($source, $locale);
            if (!\is_file($file)) {
                continue;
            }
            $values = \parse_ini_file($file, true, INI_SCANNER_RAW);
            if ($values === false) {
                throw new \RuntimeException("Invalid translation file [{$file}].");
            }
            $items = \array_replace_recursive($items, $values);
        }

        if (!$this->debug && $this->cache !== null) {
            $this->cache->write($locale, $items);
        }
        return $items;
    }

    public function flush(): bool
    {
        return $this->cache?->flush() ?? true;
    }

    private function file(string $source, string $locale): string
    {
        return \rtrim($source, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $locale . '.ini';
    }
}
