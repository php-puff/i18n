<?php

/*
 * PHP Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n;

use Psr\Container\ContainerInterface;
use Puff\Console\Contract;
use Puff\Console\Input;
use Puff\Console\Output;

final readonly class Command implements Contract
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function name(): string
    {
        return 'i18n';
    }

    public function description(): string
    {
        return 'Manage translation cache';
    }

    public function usage(): string
    {
        return 'i18n <flush|reload> [locale]';
    }

    public function valueOptions(): array
    {
        return [];
    }

    public function flagOptions(): array
    {
        return [];
    }

    public function execute(Input $input, Output $output): int
    {
        $i18n = $this->container->get(I18n::class);
        if (!$i18n instanceof I18n) {
            throw new \RuntimeException('The i18n service is not configured.');
        }

        return match ($input->argument(0)) {
            'flush' => $this->flush($i18n, $output),
            'reload' => $this->reload($i18n, $input, $output),
            default => throw new \InvalidArgumentException('I18n action must be flush or reload.'),
        };
    }

    private function flush(I18n $i18n, Output $output): int
    {
        if (!$i18n->flush()) {
            throw new \RuntimeException('Unable to clear the i18n cache.');
        }
        $output->write('I18n cache cleared.');
        return 0;
    }

    private function reload(I18n $i18n, Input $input, Output $output): int
    {
        $locale = $input->argument(1) ?? $i18n->getLocale();
        $i18n->load($locale, true);
        $output->write("I18n cache reloaded [{$i18n->getLocale()}].");
        return 0;
    }
}
