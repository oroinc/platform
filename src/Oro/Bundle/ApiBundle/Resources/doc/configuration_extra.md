# Configuration Extras

 - [Overview](#overview)
 - [ConfigExtraInterface](#configextrainterface)
 - [ConfigExtraSectionInterface](#configextrasectioninterface)
 - [Example of configuration extra](#example-of-configuration-extra)

## Overview

The configuration extras help get varying configuration information.

There are two types of the configuration extras:

- A configuration extra used to request additional configuration options for existing configuration sections. This extra is represented by `Oro\Bundle\ApiBundle\Config\ConfigExtraInterface`.
- A configuration extra used to request additional configuration sections. This extra is represented by `Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface`.

The both types of the configuration extras work in the following way:

- The actions like [get](./actions.md#get-action), [get_list](./actions.md#get_list-action), or [delete](./actions.md#delete-action) register the configuration extras in the [Context](./actions.md#context-class) using the `addConfigExtra` method. All the required extras must be registered before any of the `getConfig`, `getConfigOf`, `getConfigOfFilters`, or `getConfigOfSorters` methods of the context is called. Typically, the registration happens in processors of the `initialize` group (for example, [InitializeConfigExtras](../../Processor/Get/InitializeConfigExtras.php)).
- When a processor needs a configuration, it calls the appropriate method of the [Context](./actions.md#context-class). For example, `getConfig`, `getConfigOf`, `getConfigOfFilters`, or `getConfigOfSorters`. The first call of any of these methods causes loading of the configuration.
- The loading of the configuration is performed by the [get_config](./actions.md#get_config-action) action. Any of processors registered for this action can check which configuration data is requested. There are two ways of how a processor can find out which configuration data is requested. The first one is to use the [processor conditions](./processors.md#processor-conditions). The second one is to use the `hasExtra` method of the [ConfigContext](../../Processor/Config/ConfigContext.php).

For more details on the config structure, sections, properties, etc., see the [Configuration Reference](./configuration.md). 

## ConfigExtraInterface

The [ConfigExtraInterface](../../Config/ConfigExtraInterface.php) has the following methods:

 * **getName** - Returns a string used as a unique identifier of the configuration data.
 * **getCacheKeyPart** - Returns a string to add to a cache key used by the [configuration providers](../../Provider/AbstractConfigProvider.php). In most cases this method returns the same value as the `getName` method. However, more complicated extras can build the cache key part based on other properties, e.g. [MaxRelatedEntitiesConfigExtra](../../Config/MaxRelatedEntitiesConfigExtra.php).
 * **configureContext** - Adds additional values to the [ConfigContext](../../Processor/Config/ConfigContext.php). For example, the mentioned above [MaxRelatedEntitiesConfigExtra](../../Config/MaxRelatedEntitiesConfigExtra.php) adds the maximum number of related entities into the context of the [get_config](./actions.md#get_config-action) action and this value is used by the [SetMaxRelatedEntities](../../Processor/Config/GetConfig/SetMaxRelatedEntities.php) processor to make necessary modifications to the configuration.
 * **isPropagable** - Indicates whether to use this config extra when a configuration of related entities is built. For example, [DescriptionsConfigExtra](../../Config/DescriptionsConfigExtra.php) is propagable and so we will get human-readable descriptions of the main entity and all the related entities. When this extra is not propagable, only the descriptions of the main entity are returned.


## ConfigExtraSectionInterface

The [ConfigExtraSectionInterface](../../Config/ConfigExtraSectionInterface.php) extends [ConfigExtraInterface](../../Config/ConfigExtraInterface.php) and has one additional method:

 * **getConfigType** - Returns the configuration type that should be loaded into the corresponding section. The [ConfigLoaderFactory](../../Config/ConfigLoaderFactory.php) uses the return value of this method to find the appropriate loader.

There is a list of existing configuration extras that implement this interface:

- [FiltersConfigExtra](../../Config/FiltersConfigExtra.php)
- [SortersConfigExtra](../../Config/SortersConfigExtra.php)

## Example of Configuration Extra

The [DescriptionsConfigExtra](../../Config/DescriptionsConfigExtra.php) is used to request human-readable descriptions of entities and their fields:

```php
<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class DescriptionsConfigExtra implements ConfigExtraInterface
{
    const NAME = 'descriptions';

    public function getName()
    {
        return self::NAME;
    }

    public function configureContext(ConfigContext $context)
    {
        // No modifications of the ConfigContext are required.
    }

    public function isPropagable()
    {
        return true;
    }

    public function getCacheKeyPart()
    {
        return self::NAME;
    }
}
```

Usually configuration extras are added to the context by the `InitializeConfigExtras` processors which belong to the `initialize` group, e.g. the [InitializeConfigExtras](../../Processor/Get/InitializeConfigExtras.php) processor for the `get` action. However, the data API documentation requires human-readable descriptions. Therefore, [DescriptionsConfigExtra](../../Config/DescriptionsConfigExtra.php) is added by [RestDocHandler](../../ApiDoc/RestDocHandler.php).

The [CompleteDescriptions](../../Processor/Config/Shared/CompleteDescriptions.php) processor adds descriptions for entity, fields, and filters. This processor is registered as a service in [processors.get_config.yml](../config/processors.get_config.yml). Please note that the processor tag contains the `extra` attribute with the `descriptions&definition` value. This means that the processor is executed only if the extra configuration (in this case `description` and `definition`) were requested. For more details, see [processor conditions](./processors.md#processor-conditions).
