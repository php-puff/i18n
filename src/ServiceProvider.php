<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n;

use Puff\Config\Config;
use Puff\Di\ServiceProvider as BaseServiceProvider;
use Puff\I18n\Loader\IniLoader;
use Puff\I18n\Loader\LoaderInterface;

final class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoaderInterface::class, function (): IniLoader {
            $config = $this->app->get(Config::class);
            $sources = \array_values(\array_filter(
                (array) $config->get('i18n.sources', []),
                'is_string',
            ));
            $cache = $config->get('i18n.cache');
            $cache = \is_string($cache) ? \trim($cache) : null;

            return new IniLoader(
                $sources,
                $cache !== null && $cache !== '' ? new Cache($cache) : null,
                \getenv('PUFF_WATCH') === '1',
            );
        });
        $this->app->singleton(Detector::class, function (): Detector {
            $config = $this->app->get(Config::class);

            return new Detector(
                (string) $config->get('i18n.default', $config->get('language', 'en-US')),
                $this->app->get(LoaderInterface::class),
                (string) $config->get('i18n.query', 'i18n'),
                (string) $config->get('i18n.cookie', 'i18n'),
            );
        });
        $this->app->scoped(Context::class, fn (): Context => new Context(
            $this->app->get(Detector::class)->default(),
        ));
        $this->app->scoped(I18n::class, fn (): I18n => new I18n(
            $this->app->get(LoaderInterface::class),
            $this->app->get(Context::class),
        ));
        $this->app->alias(I18n::class, 'i18n');
    }
}
