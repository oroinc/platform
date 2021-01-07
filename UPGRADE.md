This file includes only the most important items that should be addressed before attempting to upgrade or during the upgrade of a vanilla Oro application.

Please refer to [CHANGELOG.md](CHANGELOG.md) for a list of significant changes in the code that may affect the upgrade of some customizations.

## FROM 4.1.0 to 4.2.0

The minimum required PHP version is 7.4.11.

The File storage component was implement. Directories `var/attchment` and `var/import_export` are no longer used as storage
and has been removed from the git source code.

Files from these directories must be moved to new locations:

 - files from `var/attachment` to `var/data/attachments`;
 - files from `var/attachment/protected_mediacache` to `var/data/protected_mediacache`;
 - files from `var/import_export` to `var/data/importexport`.
 
Files for import should be placed into `var/data/import_files` instead of `var/import_export/files`.

Directory `public/uploads` has been removed.

## FROM 4.1.0-rc to 4.1.0

* The feature toggle for WEB API was implemented. After upgrade, the API feature will be disabled.
To enable it please follow the documentation [Enabling an API Feature](https://doc.oroinc.com/api/enabling-api-feature/).

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
