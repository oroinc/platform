Configuration
=============

Table of contents
----------------

- [Debug translator](#debug-translator)
- [Debug js translations](#debug-js-translations)

Debug translator
----------------

Debug translator allows developer to easy check and debug translations on UI. To enable it developer have to set
option `debug_translator` to `true` in config.yml file:

```yml
oro_translation:
    debug_translator: true
```

Also developer should refresh backend and browser cache. After that all translated strings will be wrapped into
brackets, and not translated strings will be wrapped into exclamation marks with dashes. Frontend translations
have suffix "JS" to distinguish them from backend translations.

```
[Contact] - translated backend string
!!!---Account---!!! - not translated backend string

[Reset]JS - translated frontend string
!!!---Refresh---!!!JS - not translated frontend string
```

Debug JS translations
---------------------

Debug JS translations allows to turn off on fly JS translations generation, it can
slightly boost performance on slow hardware configurations and also makes app more
stable on Windows. If `kernel.debug` is set to `false` value of debug JS translations
is ignored. To turn off JS translations generation set option `js_translation.debug`
to `false` in config.yml file:

```yml
oro_translation:
    js_translation:
        debug: false
```

If you turned off JS translations generation you must do it manually by executing following command:

```bash
php bin/console oro:translation:dump
```
