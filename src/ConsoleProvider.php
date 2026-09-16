<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n;

use Psr\Container\ContainerInterface;
use Puff\Console\CommandProvider;
use Puff\Console\Contract;

/** Registers translation-cache management commands. */
final class ConsoleProvider implements CommandProvider
{
    /** @return iterable<Contract> */
    public function commands(string $root, ContainerInterface $container): iterable
    {
        yield new Command($container);
    }
}
