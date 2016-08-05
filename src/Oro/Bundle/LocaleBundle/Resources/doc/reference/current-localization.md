Current Localization
====================

Table of Contents
-----------------
 - [Receive Current Localization](#receive-current-localization)
 - [Provide Current Localization](#provide-current-localization)

Receive Current Localization
============================

For receive current localization use `Oro\Bundle\LocaleBundle\Helper\LocalizationHelper::getCurrentLocalization()` or `oro_locale.helper.localization->getCurrentLocalization()`

Provide Current Localization
============================

For provide current localization, need create custom extension, implement `Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface`, and register it by tag `oro_locale.extension.current_localization`.

```yml
services:
    ...
    acme_demo.extension.current_localization:
        class: 'Acme\Bundle\DemoBundle\Extension\CurrentLocalizationExtension'
        arguments:
            ...
        tags:
            - { name: oro_locale.extension.current_localization }
```

```php
<?php

namespace Acme\Bundle\DemoBundle\Extension;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;

class CurrentLocalizationExtension implements CurrentLocalizationExtensionInterface
{
    /**
     * @return Localization|null
     */
    public function getCurrentLocalization()
    {
        // your custom logic to receive current localization
    }
}
```