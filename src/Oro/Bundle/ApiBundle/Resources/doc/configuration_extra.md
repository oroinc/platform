Configuration Extras
====================

Table of Contents
-----------------
 - [Overview](#overview)
 - [ConfigExtraInterface](#configextrainterface)
 - [ConfigExtraSectionInterface](#configextrasectioninterface)
 - [Example of configuration extra](#example-of-configuration-extra)

Overview
--------

The configuration extras are the way to get varying configuration information.

There are two types of the configuration extras:

- a configuration extra that is used to request additional configuration options for existing configuration sections. This extra is represented by `Oro\Bundle\ApiBundle\Config\ConfigExtraInterface`.
- a configuration extra that is used to request additional configuration section. This extra is represented by `Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface`.

The both types of the configuration extras works in the following way:

- The actions like [get](./actions.md#get-action), [get_list](./actions.md#get_list-action) or [delete](./actions.md#delete-action) register the configuration extras in the [Context](./actions.md#context-class) using the `addConfigExtra` method. All required extras must be registered before any of the `getConfig`, `getConfigOf`, `getConfigOfFilters` or `getConfigOfSorters` methods of the Context is called. Typically the registration happens in processors from `initialize` group. For example [InitializeConfigExtras](../../Processor/Get/InitializeConfigExtras.php).
- When some processor needs a configuration it calls appropriate method of the [Context](./actions.md#context-class). For example `getConfig`, `getConfigOf`, `getConfigOfFilters` or `getConfigOfSorters`. The first call of any of these methods causes the loading of the configuration.
- The loading of the configuration is performed by the [get_config](./actions.md#get_config-action) action. Any of processors registered for this action can check which configuration data is requested and will do suitable work. There are two ways how a processor can find out which configuration data is requested. The first is to use the [processor conditions](./processors.md#processor-conditions). The second one is to use the `hasExtra` method of the [ConfigContext](../../Processor/Config/ConfigContext.php).

Also, please take a look into [Configuration Reference](./configuration.md) for more details about config structure, sections, properties etc.

ConfigExtraInterface
--------------------

The [ConfigExtraInterface](../../Config/ConfigExtraInterface.php) has the following methods:

 * **getName** - Returns a string which is used as unique identifier of configuration data.
 * **getCacheKeyPart** - Returns a string that should be added to a cache key used by [configuration providers](../../Provider/AbstractConfigProvider.php). In most cases this method returns the same value as the `getName` method. But some more complicated extras can build the cache key part based on own properties, e.g. [MaxRelatedEntitiesConfigExtra](../../Config/MaxRelatedEntitiesConfigExtra.php).
 * **configureContext** - This method can be used to add additional values into the [ConfigContext](../../Processor/Config/ConfigContext.php). For example, the mentioned above [MaxRelatedEntitiesConfigExtra](../../Config/MaxRelatedEntitiesConfigExtra.php) adds the maximum number of related entities into the context of the [get_config](./actions.md#get_config-action) action and this value is used by the [SetMaxRelatedEntities](../../Processor/Config/GetConfig/SetMaxRelatedEntities.php) processor to make necessary modifications to the configuration.
 * **isPropagable** - Indicates whether this config extra should be used when a configuration of related entities will be built. For example [DescriptionsConfigExtra](../../Config/DescriptionsConfigExtra.php) is propagable and it means that we will get human-readable descriptions of main entity and all the related entities. If this extra was not propagable the descriptions of main entity would be returned.


ConfigExtraSectionInterface
---------------------------

The [ConfigExtraSectionInterface](../../Config/ConfigExtraSectionInterface.php) extends [ConfigExtraInterface](../../Config/ConfigExtraInterface.php) and has one additional method:

 * **getConfigType** - Returns the configuration type that should be loaded into this section. This string is used by [ConfigLoaderFactory](../../Config/ConfigLoaderFactory.php) to find the appropriate loader.

There are a list of existing configuration extras that implement this interface:

- [FiltersConfigExtra](../../Config/FiltersConfigExtra.php)
- [SortersConfigExtra](../../Config/SortersConfigExtra.php)

Example of configuration extra
------------------------------

Let's take a look into [DescriptionsConfigExtra](../../Config/DescriptionsConfigExtra.php) which is used to request human-readable descriptions of entities and its' fields.

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
        // no any modifications of the ConfigContext is required
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

Usually configuration extras are added to the Context by `InitializeConfigExtras` processors which belong to `initialize` group, e.g. [InitializeConfigExtras](../../Processor/Get/InitializeConfigExtras.php) processor for `get` action. But human-readable descriptions are required only for generation a documentation for Data API. So, [DescriptionsConfigExtra](../../Config/DescriptionsConfigExtra.php) is added by [RestDocHandler](../../ApiDoc/RestDocHandler.php).

There are a couple of processors that add descriptions for entity, fields and filters:

 - [SetDescriptionForEntity](../../Processor/Config/GetConfig/SetDescriptionForEntity.php)
 - [SetDescriptionForFields](../../Processor/Config/Shared/SetDescriptionForFields.php)
 - [SetDescriptionForFilters](../../Processor/Config/Shared/SetDescriptionForFilters.php)

All those processors registered as services in [processors.get_config.yml](../config/processors.get_config.yml).

For example

```yaml
    ...
    oro_api.get_config.set_description_for_entity:
        class: Oro\Bundle\ApiBundle\Processor\Config\GetConfig\SetDescriptionForEntity
        arguments:
            - @oro_entity.entity_class_name_provider
            - @oro_entity_config.provider.entity
        tags:
            - { name: oro.api.processor, action: get_config, extra: definition&descriptions, priority: -200 }
    ...
```

Please note, the processor tag contains the `extra` attribute with `definition&descriptions` value. This means that the processor will be executed only if the extra configuration (in this case `definition` and `description`) were requested. For more details see [processor conditions](./processors.md#processor-conditions).
