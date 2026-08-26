<?php

/*
 * PHP Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Puff\Di\Container;
use Puff\I18n\Loader\LoaderInterface;

final readonly class Pipeline
{
    public function __construct(
        private Container $container,
        private Detector $detector,
        private LoaderInterface $loader,
    ) {
    }

    public function handle(ServerRequestInterface $request, Closure $next, mixed ...$arguments): mixed
    {
        $context = new Context($this->detector->default());
        $context->set($this->detector->detect($request));
        $this->container->scopedInstance(Context::class, $context);
        $this->container->scopedInstance(I18n::class, new I18n($this->loader, $context));

        return $next($request->withAttribute('locale', $context->get()));
    }
}
