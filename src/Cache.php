<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n;

final readonly class Cache
{
    public function __construct(private string $directory)
    {
    }

    /** @return array<string, mixed>|null */
    public function read(string $locale): ?array
    {
        $file = $this->file($locale);
        if (!\is_file($file)) {
            return null;
        }
        $items = require $file;
        return \is_array($items) ? $items : null;
    }

    /** @param array<string, mixed> $items */
    public function write(string $locale, array $items): void
    {
        if (!\is_dir($this->directory)
            && !\mkdir($this->directory, 0755, true)
            && !\is_dir($this->directory)
        ) {
            throw new \RuntimeException("Cannot create translation cache directory [{$this->directory}].");
        }

        $temporary = \tempnam($this->directory, 'puff-i18n-');
        if ($temporary === false) {
            throw new \RuntimeException("Cannot create translation cache file in [{$this->directory}].");
        }
        try {
            $contents = '<?php return ' . \var_export($items, true) . ';';
            if (\file_put_contents($temporary, $contents, LOCK_EX) === false
                || !\rename($temporary, $this->file($locale))
            ) {
                throw new \RuntimeException("Cannot write translation cache [{$this->file($locale)}].");
            }
        } finally {
            if (\is_file($temporary)) {
                \unlink($temporary);
            }
        }
    }

    public function flush(): bool
    {
        if (!\is_dir($this->directory)) {
            return true;
        }

        $success = true;
        foreach (new \FilesystemIterator($this->directory) as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            if (($file->isFile() || $file->isLink()) && $this->isCacheFile($file->getFilename())) {
                $success = \unlink($file->getPathname()) && $success;
            }
        }
        return $success;
    }

    private function file(string $locale): string
    {
        $locale = Locale::normalize($locale);
        return \rtrim($this->directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $locale . '.php';
    }

    private function isCacheFile(string $filename): bool
    {
        if (!\str_ends_with($filename, '.php')) {
            return false;
        }
        $locale = \substr($filename, 0, -4);
        return Locale::tryNormalize($locale) === $locale;
    }
}
