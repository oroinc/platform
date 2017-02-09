Managing Localizations
======================
Table of Contents
-----------------
 - [Caching notice](#caching-notice)
 - [LocalizationManager](#localizationmanager)

Caching notice
==============

Localization objects are cached to provide better performance.
You _MUST_ disable cache usage if you need to persist, delete or assign returned Localization
(see *$useCache* parameter below).

LocalizationManager
===================
LocalizationManager provides all necessary methods for accessing localizations.
You can easly access it from the controller:

```php
$this->get('oro_locale.manager.localization');
```

**Oro\Bundle\LocaleBundle\Manager::getLocalization($id\[, $useCache = true\])**

Gets single Localization object.

Example of usage:

```php
// Will get single Localization object
$localizationManager = $this->get('oro_locale.manager.localization');
$localization = $localizationManager->getLocalization(1);
```

**Oro\Bundle\LocaleBundle\Manager::getLocalizations(array $ids\[, $useCache = true\])**

Gets one or selected Localization objects.

Example of usage:
```php
$localizationManager = $this->get('oro_locale.manager.localization');

// Will get all Localizations from database
$localizations = $localizationManager->getLocalizations();

// Will get Localizations with ids 1, 3, 5, 7. 
// If there is no Localization with provided id, this id will be skipped. 
$localizations = $localizationManager->getLocalizations([1, 3, 5, 7]);
```

**Oro\Bundle\LocaleBundle\Manager::getDefaultLocalization(\[$useCache = true\])**

Gets default Localization. Default Localization is obtain from system configuration
(see [OroConfigBundle](../../../../ConfigBundle/Resources/doc/system_configuration.md) for more informations).

Example of usage:
```php
$localizationManager = $this->get('oro_locale.manager.localization');
$defaultLocalization = $localizationManager->getDefaultLocalization();
```

**Oro\Bundle\LocaleBundle\Manager::clearCache**

Removes Localization objects from cache. Next time user will call getLocalization,
getLocalizations, getDefaultLocalization or warmUpCache, cache will be recreated.

Example of usage:
```php
$localizationManager = $this->get('oro_locale.manager.localization');
$defaultLocalization = $localizationManager->clearCache();
```

_NOTICE:_ Keep in mind, that cache is cleared also when you run

```text
php app/console cache:clear
```

**Oro\Bundle\LocaleBundle\Manager::warmUpCache**

Gets all Localization objects from database and stores them into cache.

Example of usage:
```php
$localizationManager = $this->get('oro_locale.manager.localization');
$defaultLocalization = $localizationManager->warmUpCache();
```

_NOTICE:_ Keep in mind, that cache is warmed up also when you run

```text
php app/console cache:clear
```
