<?php

/*
 * PHP Fiber Framework
 * https://github.com/php-puff/i18n
 * https://github.com/php-puff/i18n/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\I18n\Tests;

use Fiber;
use PHPUnit\Framework\TestCase;
use Puff\Config\Config;
use Puff\Console\Console;
use Puff\Console\Output;
use Puff\Di\Container;
use Puff\Http\Request;
use Puff\I18n\Cache;
use Puff\I18n\Command;
use Puff\I18n\Context;
use Puff\I18n\Detector;
use Puff\I18n\I18n;
use Puff\I18n\Loader\IniLoader;
use Puff\I18n\Locale;
use Puff\I18n\Pipeline;
use Puff\I18n\ServiceProvider;

final class I18nTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir() . '/puff-i18n-' . \bin2hex(\random_bytes(6));
        self::assertTrue(\mkdir($this->directory, 0755, true));
        \file_put_contents($this->directory . '/en-US.ini', "[welcome]\nmessage = \"Hello %s\"\n");
        \file_put_contents($this->directory . '/zh-CN.ini', "[welcome]\nmessage = \"你好 %s\"\n");
    }

    protected function tearDown(): void
    {
        Container::setInstance();
        foreach (\glob($this->directory . '/*') ?: [] as $file) {
            if (\is_file($file)) {
                \unlink($file);
            }
        }
        \rmdir($this->directory);
    }

    public function testPackagePublishesCompleteConfiguration(): void
    {
        $manifest = \json_decode(
            (string) \file_get_contents(\dirname(__DIR__) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $configuration = require \dirname(__DIR__) . '/config/i18n.php';

        self::assertSame(
            ['i18n.php' => 'config/i18n.php'],
            $manifest['extra']['puff']['config'] ?? null,
        );
        self::assertIsArray($configuration);
        self::assertSame(
            ['default', 'sources', 'cache', 'query', 'cookie'],
            \array_keys($configuration),
        );
    }

    public function testTranslatorLoadsAndSwitchesLocale(): void
    {
        $lang = new I18n(new IniLoader([$this->directory]), new Context('en_US'));

        self::assertSame('Hello Puff', $lang->get('welcome.message', 'Puff'));
        self::assertSame('zh-CN', $lang->setLocale('zh_CN')->getLocale());
        self::assertSame('你好 Puff', $lang->get('welcome.message', 'Puff'));
        self::assertSame('en-US', $lang->setLocale('fr-FR')->getLocale());
    }

    public function testDetectorUsesRequestLocalValuesInPriorityOrder(): void
    {
        $detector = new Detector('en_US', new IniLoader([$this->directory]));
        $request = new Request(
            headers: ['Accept-Language' => 'en-US,en;q=0.8'],
            queryParams: ['i18n' => 'zh_CN'],
            cookieParams: ['i18n' => 'en_US'],
            attributes: ['locale' => 'en_US'],
        );

        self::assertSame('zh-CN', $detector->detect($request));
    }

    public function testDetectorRejectsMissingAndUnsafeLocales(): void
    {
        $detector = new Detector('en_US', new IniLoader([$this->directory]));

        self::assertSame('en-US', $detector->detect(new Request(queryParams: ['i18n' => 'fr-FR'])));
        self::assertSame('en-US', $detector->detect(new Request(queryParams: ['i18n' => '../../config'])));
        self::assertSame('en-US', $detector->detect(new Request(queryParams: ['i18n' => 'zh_CN.php'])));
    }

    public function testDetectorHonorsAcceptLanguageQuality(): void
    {
        $detector = new Detector('en-US', new IniLoader([$this->directory]));
        $request = new Request(headers: [
            'Accept-Language' => 'zh-CN;q=0.1, en_US;q=0.9, fr;q=0, de;q=invalid',
        ]);

        self::assertSame('en-US', $detector->detect($request));
    }

    public function testLoaderResolvesAvailableTranslationFiles(): void
    {
        $loader = new IniLoader([$this->directory]);

        self::assertSame('zh-CN', $loader->resolve('zh_CN'));
        self::assertNull($loader->resolve('fr-FR'));
    }

    public function testLocaleFallsBackFromRegionAndScriptToLanguage(): void
    {
        \file_put_contents($this->directory . '/zh.ini', "[welcome]\nmessage = \"你好 %s\"\n");
        $loader = new IniLoader([$this->directory]);
        $translator = new I18n($loader, new Context('zh-Hant-HK'));

        self::assertSame(['zh-Hant-HK', 'zh-Hant', 'zh'], Locale::fallbacks('zh_Hant_HK'));
        self::assertSame('zh', $loader->resolve('zh-Hant-HK'));
        self::assertSame('zh', $translator->getLocale());
        self::assertSame('你好 Puff', $translator->get('welcome.message', 'Puff'));
    }

    public function testLoaderCachesAndFlushesTranslations(): void
    {
        $cache = $this->directory . '/cache';
        $loader = new IniLoader([$this->directory], new Cache($cache));

        self::assertSame(['welcome' => ['message' => 'Hello %s']], $loader->load('en_US'));
        self::assertFileExists($cache . '/en-US.php');
        self::assertTrue($loader->flush());
        self::assertFileDoesNotExist($cache . '/en-US.php');
        \rmdir($cache);
    }

    public function testCacheRejectsPathTraversalAndPreservesUnrelatedFiles(): void
    {
        $directory = $this->directory . '/cache';
        $cache = new Cache($directory);
        $cache->write('en-US', ['message' => 'Hello']);
        \file_put_contents($directory . '/i18n-zh-CN.php', '<?php return [];');
        \file_put_contents($directory . '/unrelated.php', '<?php return true;');

        try {
            $cache->read('../../config');
            self::fail('Unsafe locale was accepted.');
        } catch (\InvalidArgumentException) {
            self::assertTrue($cache->flush());
            self::assertFileExists($directory . '/i18n-zh-CN.php');
            self::assertFileExists($directory . '/unrelated.php');
        }

        \unlink($directory . '/i18n-zh-CN.php');
        \unlink($directory . '/unrelated.php');
        \rmdir($directory);
    }

    public function testConsoleReloadsAndFlushesCache(): void
    {
        $cache = $this->directory . '/cache';
        $translator = new I18n(
            new IniLoader([$this->directory], new Cache($cache)),
            new Context('en-US'),
        );
        $container = new Container();
        $container->instance(I18n::class, $translator);
        $stdout = \fopen('php://memory', 'w+');
        self::assertIsResource($stdout);
        $console = new Console([new Command($container)], new Output($stdout));

        \file_put_contents($this->directory . '/en-US.ini', "[welcome]\nmessage = \"Updated %s\"\n");
        self::assertSame(0, $console->run(['puff', 'i18n', 'reload', 'en-US']));
        self::assertSame('Updated Puff', $translator->get('welcome.message', 'Puff'));
        self::assertFileExists($cache . '/en-US.php');

        self::assertSame(0, $console->run(['puff', 'i18n', 'flush']));
        self::assertFileDoesNotExist($cache . '/en-US.php');
        \fclose($stdout);
        \rmdir($cache);
    }

    public function testPipelineKeepsLocalesInsideTheirFiberScope(): void
    {
        $container = new Container();
        $loader = new IniLoader([$this->directory]);
        $pipeline = new Pipeline(
            $container,
            new Detector('en_US', $loader),
            $loader,
        );
        $locale = static fn () => Fiber::suspend($container->get(I18n::class)->getLocale());
        $chinese = new Fiber(fn () => $pipeline->handle(
            new Request(queryParams: ['i18n' => 'zh_CN']),
            $locale,
        ));
        $english = new Fiber(fn () => $pipeline->handle(
            new Request(queryParams: ['i18n' => 'en_US']),
            $locale,
        ));

        self::assertSame('zh-CN', $chinese->start());
        self::assertSame('en-US', $english->start());
        $chinese->resume();
        $english->resume();
    }

    public function testProviderRegistersDefaultTranslatorAndHelpers(): void
    {
        $container = new Container();
        $container->instance(Config::class, new Config([
            'i18n' => [
                'default' => 'en-US',
                'sources' => [$this->directory],
                'query' => 'lang',
            ],
        ]));
        Container::setInstance($container);
        (new ServiceProvider($container))->register();

        self::assertSame('Hello Puff', \lang('welcome.message', 'Puff'));
        self::assertSame('en-US', \i18n());
        self::assertSame('zh-CN', $container->get(Detector::class)->detect(
            new Request(queryParams: ['lang' => 'zh-CN']),
        ));
    }

    public function testProviderScopesTranslatorPerFiber(): void
    {
        $container = new Container();
        $container->instance(Config::class, new Config([
            'i18n' => [
                'default' => 'en-US',
                'sources' => [$this->directory],
            ],
        ]));
        (new ServiceProvider($container))->register();

        $first = null;
        $fiber = new Fiber(function () use ($container, &$first): void {
            $first = $container->get(I18n::class);
            $first->setLocale('zh-CN');
            self::assertSame($first, $container->get(I18n::class));
        });
        $fiber->start();

        self::assertSame('zh-CN', $first?->getLocale());
        self::assertSame('en-US', $container->get(I18n::class)->getLocale());
    }
}
