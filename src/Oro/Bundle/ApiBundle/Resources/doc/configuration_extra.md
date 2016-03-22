Configuration Extra & ExtraSection Reference
============================================

Table of Contents
-----------------
 - [Overview](#overview)
 - [ConfigExtraInterface](#configextrainterface)
 - [ConfigExtraSectionInterface](#configextrasectioninterface)

Overview
--------

Config extras are the way to get varying configuration information. 

For cases when some additional configuration property is required in a configuration the [ConfigExtraInterface](#configextrainterface) should be used.

Additionally, to the `ConfigExtraInterface` if some additional configuration section is required the [ConfigExtraSectionInterface](#configextrasectioninterface) should be used. This interface can be used to tell the Context that an additional data should be available as additional configuration section. So, the methods "hasConfigOf", "getConfigOf" and "setConfigOf" inside the context can be used to manipulate those data.

The main difference between `ConfigExtraInterface` and `ConfigExtraSectionInterface` is that the last one manipulates with a whole configuration section instead a single property.

Also, please take a look into [Configuration Reference](./configuration.md) for more details about config structure, sections, properties etc. 

ConfigExtraInterface
--------------------

Interface class: [ConfigExtraInterface](../../Config/ConfigExtraInterface.php)

Methods:

 * **getName** - Gets the unique string identifier of additional data.
 * **getCacheKeyPart** - Gets a string that should be used as a part of a cache key used by config providers.
 
 Both methods `getName` and `getCachedKeyPart` in most cases will returns the same value because it's applicable as unique identifier as well as a cache key part.
 
 * **configureContext** - Modifies the context ([ConfigContext](../../Processor/Config/ConfigContext.php)) to make it possible to get required additional data. The method will be empty if context do not need any modifications. The more complex example is [FilterFieldsConfigExtra](../../Config/FilterFieldsConfigExtra.php).
 * **isPropagable** - The method indicates whether this config extra is applicable to nested configs.

And as a simplest example take a look into [DescriptionConfigExtra](../../Processor/Config/ConfigContext/DescriptionsConfigExtra.php) - the extra configuration to add a human-readable descriptions of entities and its' fields. 

```php
<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class DescriptionsConfigExtra implements ConfigExtraInterface
{
    const NAME = 'descriptions';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ConfigContext $context)
    {
        // no any modifications of the ConfigContext is required
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return self::NAME;
    }
}
```


But the only creation of a class that implements the `ConfigExtraInterface` not enough. To add extra configuration some [processor](./processors.md#overview) should call `addConfigExtra` with new instance of that extra configuration as an argument. Typically the instantiation of extra configuration is the responsibility of some processor from `initialization` group, but depending on actual needs it can be added in any processor.


As an example:

```yaml
    oro_api.get.initialize_config_extras:
        class: Oro\Bundle\ApiBundle\Processor\Get\InitializeConfigExtras
        tags:
            - { name: oro.api.processor, action: get, group: initialize, priority: 10 }
```

```php
<?php

namespace Oro\Bundle\ApiBundle\Processor\Get;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Sets an initial list of requests for additional configuration data.
 */
class InitializeConfigExtras implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $context->addConfigExtra(new EntityDefinitionConfigExtra($context->getAction()));
        $context->addConfigExtra(new FiltersConfigExtra());
    }
}
```

After the extra configuration have been added, some another processor(s) should be added to take the responsibility for manipulating the Context in order to realize the logic for which the configuration extra was designed for. So, as mentioned in first example, the extra configuration [DescriptionsConfigExtra](../../Processor/Config/ConfigContext/DescriptionsConfigExtra.php) tells that a human-readable descriptions should be added. And there are a couple of processors that add a description for [entity](../../Processor/Config/GetConfig/SetDescriptionForEntity.php), [fields](../../Processor/Config/Shared/SetDescriptionForFields.php) and [filters](../../Processor/Config/Shared/SetDescriptionForFilters.php) respectively.

All those processors registered as a service, e.g. [processors.get_config.yml](../config/processors.get_config.yml)

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

Please note, the processor tagged with an extra attribute - `extra: definition&descriptions`. This means that the processor will be executed only if the extra configuration, in this case `definition` and `description` were requested. For more details see [processors](./processors.md#processor-conditions).


ConfigExtraSectionInterface
---------------------------

Interface class: [ConfigExtraSectionInterface](../../Config/ConfigExtraSectionInterface.php).

Methods:
 * **getConfigType** - Gets the configuration type that can be loaded into this section.

As mentioned at the beginning classes that implements a `ConfigExtraSectionInterface` works with whole sections.
As an examples take a look into [filters](../../Config/FiltersConfigExtra.php), [sorters](../../Config/SortersConfigExtra.php) and the  processors to complete [filters](../../Processor/Config/Shared/CompleteFilters.php) and [sorters](../../Processor/Config/Shared/CompleteSorters.php) respectively.
