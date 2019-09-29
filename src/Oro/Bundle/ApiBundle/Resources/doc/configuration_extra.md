# Configuration Extras

 - [Overview](#overview)
 - [ConfigExtraInterface](#configextrainterface)
 - [ConfigExtraSectionInterface](#configextrasectioninterface)
 - [Example of configuration extra](#example-of-configuration-extra)

## Overview

The configuration extras help get varying configuration information.

There are two types of the configuration extras:

- A configuration extra used to request additional configuration options for existing configuration sections. This extra is represented by `Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface`.
- A configuration extra used to request additional configuration sections. This extra is represented by `Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraSectionInterface`.

The both types of the configuration extras work in the following way:

- The actions like [get](./actions.md#get-action), [get_list](./actions.md#get_list-action), or [delete](./actions.md#delete-action) register the configuration extras in the [Context](./actions.md#context-class) using the `addConfigExtra` method. All the required extras must be registered before any of the `getConfig`, `getConfigOf`, `getConfigOfFilters`, or `getConfigOfSorters` methods of the context is called. Typically, the registration happens in processors of the `initialize` group (for example, [InitializeConfigExtras](../../Processor/Get/InitializeConfigExtras.php)).
- When a processor needs a configuration, it calls the appropriate method of the [Context](./actions.md#context-class). For example, `getConfig`, `getConfigOf`, `getConfigOfFilters`, or `getConfigOfSorters`. The first call of any of these methods causes loading of the configuration.
- The loading of the configuration is performed by the [get_config](./actions.md#get_config-action) action. Any of processors registered for this action can check which configuration data is requested. There are two ways of how a processor can find out which configuration data is requested. The first one is to use the [processor conditions](./processors.md#processor-conditions). The second one is to use the `hasExtra` method of the [ConfigContext](../../Processor/GetConfig/ConfigContext.php).

For more details on the config structure, sections, properties, etc., see the [Configuration Reference](./configuration.md). 

## ConfigExtraInterface

The [ConfigExtraInterface](../../Config/Extra/ConfigExtraInterface.php) has the following methods:

 * **getName** - Returns a string used as a unique identifier of the configuration data.
 * **getCacheKeyPart** - Returns a string to add to a cache key used by the [configuration providers](../../Provider/AbstractConfigProvider.php). In most cases this method returns the same value as the `getName` method. However, more complicated extras can build the cache key part based on other properties, e.g. [MaxRelatedEntitiesConfigExtra](../../Config/Extra/MaxRelatedEntitiesConfigExtra.php).
 * **configureContext** - Adds additional values to the [ConfigContext](../../Processor/GetConfig/ConfigContext.php). For example, the mentioned above [MaxRelatedEntitiesConfigExtra](../../Config/Extra/MaxRelatedEntitiesConfigExtra.php) adds the maximum number of related entities into the context of the [get_config](./actions.md#get_config-action) action and this value is used by the [SetMaxRelatedEntities](../../Processor/GetConfig/SetMaxRelatedEntities.php) processor to make necessary modifications to the configuration.
 * **isPropagable** - Indicates whether this config extra should be used when a configuration of related entities is built. For example, [DataTransformersConfigExtra](../../Config/Extra/DataTransformersConfigExtra.php) is propagable and as result field value data transformers will be returned for the main entity and all related entities.


## ConfigExtraSectionInterface

The [ConfigExtraSectionInterface](../../Config/Extra/ConfigExtraSectionInterface.php) extends [ConfigExtraInterface](../../Config/Extra/ConfigExtraInterface.php) and has one additional method:

 * **getConfigType** - Returns the configuration type that should be loaded into the corresponding section. The [ConfigLoaderFactory](../../Config/Loader/ConfigLoaderFactory.php) uses the return value of this method to find the appropriate loader.

There is a list of existing configuration extras that implement this interface:

- [FiltersConfigExtra](../../Config/Extra/FiltersConfigExtra.php)
- [SortersConfigExtra](../../Config/Extra/SortersConfigExtra.php)

## Example of Configuration Extra

The [DescriptionsConfigExtra](../../Config/Extra/DescriptionsConfigExtra.php) is used to request human-readable descriptions of entities and their fields:

```php
<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

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
        return false;
    }

    public function getCacheKeyPart()
    {
        return self::NAME;
    }
}
```

Usually configuration extras are added to the context by the `InitializeConfigExtras` processors which belong to the `initialize` group, e.g. the [InitializeConfigExtras](../../Processor/Get/InitializeConfigExtras.php) processor for the `get` action. However, the API documentation requires human-readable descriptions. Therefore, [DescriptionsConfigExtra](../../Config/Extra/DescriptionsConfigExtra.php) is added by [RestDocHandler](../../ApiDoc/RestDocHandler.php).

The [CompleteDescriptions](../../Processor/GetConfig/CompleteDescriptions.php) processor adds descriptions for entity, fields, and filters. This processor is registered as a service in [processors.get_config.yml](../config/processors.get_config.yml). Please note that the processor tag contains the `extra` attribute with the `descriptions&definition` value. This means that the processor is executed only if the extra configuration (in this case `description` and `definition`) were requested. For more details, see [processor conditions](./processors.md#processor-conditions).
