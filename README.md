# Puff I18n

Fiber-safe localization and translation for Puff.

## Configuration

```php
'i18n' => [
    'default' => 'en',
    'sources' => [
        dirname(__DIR__) . '/i18n',
        dirname(__DIR__) . '/app/I18n',
    ],
    'cache' => dirname(__DIR__) . '/runtime/lang',
    'query' => 'i18n',
    'cookie' => 'i18n',
],
```

## Translation files

Translations use `.ini` files named after their normalized locale, for example `en.ini`, `en-US.ini`, or `zh-Hant-HK.ini`.

Top-level keys and sections are supported:

```ini
app = "Puff"
name = "PHP Unison Fiber Framework"

[welcome]
title = "Welcome"
message = "Hello %s"

[validation]
required = "%s is required"
```

Section values are addressed with dot notation. `%s` placeholders are filled in argument order:

```php
lang('app');                              // Puff
lang('welcome.message', 'Puff');
lang('validation.required', 'Email');     // Email is required
```

Multiple keys may be concatenated in one lookup:

```php
lang('welcome.title,welcome.message', 'Puff');
```

Translations can also be inspected or overridden inside the current Fiber scope:

```php
$translations = lang();
$translations->has('welcome.message');
$translations->raw('welcome');
$translations->all();
$translations->set('welcome.message', 'Hi %s');
$translations->add([
    'user.profile' => 'Profile',
]);
```

Later source directories override matching values from earlier directories:

```php
'sources' => [
    dirname(__DIR__) . '/i18n',     // package defaults
    dirname(__DIR__) . '/app/I18n', // application overrides
],
```

Only `.ini` sources are currently supported. Values are parsed with `INI_SCANNER_RAW` so translation text remains a string.

## Locale fallback

Locale identifiers accept underscores or hyphens and are normalized to BCP 47-style casing:

```php
i18n('zh_CN');      // selects zh-CN
i18n('zh_Hant_HK'); // selects zh-Hant-HK
```

If an exact file is missing, Puff tries progressively less-specific filenames:

```text
zh-Hant-HK.ini → zh-Hant.ini → zh.ini
```

This is file fallback, not translation inheritance. If `en-US.ini` exists, Puff loads it without merging `en.ini`. Files with the same selected locale are merged only across configured `sources`, with later sources taking precedence.

If no requested locale variant exists, the configured `default` locale is used.

## Request locale

Add the pipeline to `http.pipeline`:

```php
'pipeline' => [
    Puff\I18n\Pipeline::class,
],
```

The locale is detected independently for every Fiber. Detection order is query parameter, route attribute, cookie, weighted `Accept-Language`, then the configured default. Route integrations should expose their locale through the `locale` request attribute.

Use a dedicated directory for `i18n.cache`. Only cache files created by this component are removed by `I18n::flush()`.

## Cache commands

Clear every generated translation cache file:

```bash
./puff i18n flush
```

Reload the current locale from its source files and update its cache:

```bash
./puff i18n reload
```

Reload a specific locale. Locale fallback still applies, so `en-US` can update `en.php` when only `en.ini` exists:

```bash
./puff i18n reload en-US
```

`reload` bypasses the existing cache. `flush` removes current `<locale>.php` cache files. Missing cache files are regenerated automatically on the next translation lookup.
