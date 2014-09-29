Configuration
=============

Table of contents
----------------

- [Debug translator](#debug-translator)

Debug translator
----------------

Debug translator allows developer to easy check and debug translations on UI. To enable it developer have to set
option _debug\_translator_ to true in config.yml file:

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
