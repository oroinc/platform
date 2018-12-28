## FROM 3.0.0 to 3.1.0
* `oro:assets:install` command was removed, use [`assets:install`] instead.
* `oro:assetic:dump` command was removed, use [`oro:assets:build`](src/Oro/Bundle/AssetBundle/README.md) instead.
* `nodejs` and `npm` are required dependencies now
* `oro_entity.database_exception_helper` service was removed. Catch `Doctrine\DBAL\Exception\RetryableException` directly instead of helper usage.

## FROM 2.6.0 to 3.0.0
* To successfuly upgrade to 3.0.0 version which uses Symfony 3 you need to replace all form alias by their respective FQCN's in entity configs and embedded forms.
Use the following script to find out which values should be changed.
```bash
php vendor/oro/platform/bin/oro-form-alias-checker/oro-form-alias-checker
```

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
