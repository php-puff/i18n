<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n;

use Psr\Http\Message\ServerRequestInterface;
use Puff\I18n\Loader\LoaderInterface;

final readonly class Detector
{
    private string $default;

    public function __construct(
        string $default,
        private LoaderInterface $loader,
        private string $query = 'i18n',
        private string $cookie = 'i18n',
    ) {
        $this->default = $this->loader->resolve($default) ?? Locale::normalize($default);
    }

    public function detect(ServerRequestInterface $request): string
    {
        $query = $request->getQueryParams()[$this->query] ?? null;
        $route = $request->getAttribute('locale');
        $cookie = $request->getCookieParams()[$this->cookie] ?? null;

        foreach ([$query, $route, $cookie, ...$this->accepted($request)] as $locale) {
            if (!\is_string($locale)) {
                continue;
            }
            $normalized = Locale::tryNormalize($locale);
            if ($normalized !== null && ($resolved = $this->loader->resolve($normalized)) !== null) {
                return $resolved;
            }
        }
        return $this->default;
    }

    public function default(): string
    {
        return $this->default;
    }

    /** @return list<string> */
    private function accepted(ServerRequestInterface $request): array
    {
        $weighted = [];
        foreach (\explode(',', $request->getHeaderLine('Accept-Language')) as $position => $entry) {
            $parts = \array_map('trim', \explode(';', $entry));
            $locale = \array_shift($parts);
            if ($locale === '' || $locale === '*') {
                continue;
            }

            $quality = 1.0;
            foreach ($parts as $part) {
                if (!\str_starts_with($part, 'q=')) {
                    continue;
                }
                $value = \substr($part, 2);
                if (\preg_match('/^(?:0(?:\.\d{0,3})?|1(?:\.0{0,3})?)$/D', $value) !== 1) {
                    $quality = 0.0;
                    break;
                }
                $quality = (float) $value;
            }
            if ($quality > 0) {
                $weighted[] = ['locale' => $locale, 'quality' => $quality, 'position' => $position];
            }
        }

        \usort($weighted, static fn (array $left, array $right): int =>
            $right['quality'] <=> $left['quality'] ?: $left['position'] <=> $right['position']);

        return \array_column($weighted, 'locale');
    }
}
