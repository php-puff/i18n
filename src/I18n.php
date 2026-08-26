<?php

/*
 * PHP Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n;

use Puff\I18n\Loader\LoaderInterface;
use Stringable;

final class I18n
{
    /** @var array<string, mixed> */
    private array $items = [];

    public function __construct(
        private readonly LoaderInterface $loader,
        private readonly Context $context,
    ) {
        $this->load($context->get());
    }

    public function i18n(?string $locale = null): string|self
    {
        return $this->locale($locale);
    }

    public function locale(?string $locale = null): string|self
    {
        return $locale === null ? $this->getLocale() : $this->setLocale($locale);
    }

    public function setLocale(string $locale): self
    {
        $locale = Locale::normalize($locale);
        if ($locale === $this->context->get()) {
            return $this;
        }

        return $this->load($locale);
    }

    public function getLocale(): string
    {
        return $this->context->get();
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->items);
    }

    public function get(string $key, mixed ...$arguments): string
    {
        $keys = \explode(',', $key);
        return \implode('', \array_map(
            fn (string $item): string => $this->take($item, \array_values($arguments)),
            $keys,
        ));
    }

    /** @param array<string, mixed>|string $key */
    public function set(array|string $key, mixed $value = null): self
    {
        $values = \is_array($key) ? $key : [$key => $value];
        $this->items = \array_replace($this->items, self::flatten($values));
        return $this;
    }

    /** @param array<string, mixed>|string $key */
    public function add(array|string $key, mixed $value = null): self
    {
        return $this->set($key, $value);
    }

    public function raw(?string $key = null, mixed $default = []): mixed
    {
        $items = self::expand($this->items);
        if ($key === null || $key === '') {
            return $items;
        }

        $value = $items;
        foreach (\explode('.', $key) as $segment) {
            if (!\is_array($value) || !\array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->items;
    }

    public function load(string $locale, bool $reload = false): self
    {
        $locale = Locale::normalize($locale);
        $locale = $this->loader->resolve($locale)
            ?? $this->loader->resolve($this->context->default())
            ?? $this->context->default();
        $items = self::flatten($this->loader->load($locale, $reload));
        $this->context->set($locale);
        $this->items = $items;
        return $this;
    }

    public function flush(): bool
    {
        return $this->loader->flush();
    }

    /** @param list<mixed> $arguments */
    private function take(string $key, array $arguments = []): string
    {
        $value = $this->items[$key] ?? $key;
        if (!\is_scalar($value) && !$value instanceof Stringable) {
            return $key;
        }

        $line = (string) $value;
        if ($arguments === []) {
            return \str_replace('%s', '', $line);
        }
        return \vsprintf($line, \array_pad($arguments, \substr_count($line, '%s'), ''));
    }

    /** @param array<string, mixed> $items
     * @return array<string, mixed>
     */
    private static function flatten(array $items, string $prefix = ''): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            $path = $prefix . (string) $key;
            if (\is_array($value) && $value !== []) {
                $result += self::flatten($value, $path . '.');
                continue;
            }
            $result[$path] = $value;
        }
        return $result;
    }

    /** @param array<string, mixed> $items
     * @return array<string, mixed>
     */
    private static function expand(array $items): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            $target = &$result;
            foreach (\explode('.', $key) as $segment) {
                $target = &$target[$segment];
            }
            $target = $value;
            unset($target);
        }
        return $result;
    }
}
