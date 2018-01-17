## FROM 2.5.0 to 2.6.0
* Changed minimum required php version to 7.1

## FROM 2.3.0 to 2.3.1
* A full rebuilding of the backend search index is required due to tokenizer configuration has been changed.

## FROM 2.0.0 to 2.1.0
* Changed minimum required php version to 7.0
* Updated dependency to [fxpio/composer-asset-plugin](https://github.com/fxpio/composer-asset-plugin) composer plugin to version 1.3.
* Composer updated to version 1.4.

```
    composer self-update
    composer global require "fxp/composer-asset-plugin"
```
* The `oro:search:reindex` command now works synchronously by default. Use the `--scheduled` parameter if you need the old, async behaviour