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
LocalizationManager provides all the necessary methods for accessing localizations.
You can easily access it from the controller:

```php
$this->get('oro_locale.manager.localization');
```

**Oro\Bundle\LocaleBundle\Manager::getLocalization($id\[, $useCache = true\])**

Gets a single Localization object.

An example of usage:

```php
// Will get single Localization object
$localizationManager = $this->get('oro_locale.manager.localization');
$localization = $localizationManager->getLocalization(1);
```

**Oro\Bundle\LocaleBundle\Manager::getLocalizations(array $ids\[, $useCache = true\])**

Gets one or selected Localization objects.

An example of usage:
```php
$localizationManager = $this->get('oro_locale.manager.localization');

// Will get all Localizations from database
$localizations = $localizationManager->getLocalizations();

// Will get Localizations with ids 1, 3, 5, 7. 
// If there is no Localization with provided id, this id will be skipped. 
$localizations = $localizationManager->getLocalizations([1, 3, 5, 7]);
```

**Oro\Bundle\LocaleBundle\Manager::getDefaultLocalization(\[$useCache = true\])**

Gets the default Localization. Default Localization is obtained from the system configuration
(see [OroConfigBundle](../../../../ConfigBundle/Resources/doc/system_configuration.md) for more information).

An example of usage:
```php
$localizationManager = $this->get('oro_locale.manager.localization');
$defaultLocalization = $localizationManager->getDefaultLocalization();
```

**Oro\Bundle\LocaleBundle\Manager::clearCache**

Removes Localization objects from cache. The next time a user calls getLocalization,
getLocalizations, getDefaultLocalization or warmUpCache, cache will be recreated.

An example of usage:
```php
$localizationManager = $this->get('oro_locale.manager.localization');
$defaultLocalization = $localizationManager->clearCache();
```

_NOTICE:_ Keep in mind that cache is also cleared when you run

```text
php bin/console cache:clear
```

**Oro\Bundle\LocaleBundle\Manager::warmUpCache**

Gets all Localization objects from the database and stores them into cache.

An example of usage:
```php
$localizationManager = $this->get('oro_locale.manager.localization');
$defaultLocalization = $localizationManager->warmUpCache();
```

_NOTICE:_ Keep in mind that cache is also warmed up when you run

```text
php bin/console cache:clear
```
