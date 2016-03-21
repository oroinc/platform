Configuration Reference
=======================

Table of Contents
-----------------
 - [Overview](#overview)
 - [ConfigExtraInterface](#configextrainterface)
 - [ConfigExtraSectionInterface](#configextrasectioninterface)

Overview
--------

For cases when some additional configuration data are required inside a context depending on request type the [ConfigExtraInterface](#configextrainterface) should be used.

Additionally, to the `ConfigExtraInterface` if some additional configuration section is required the [ConfigExtraSectionInterface](#configextrasectioninterface) should be used. This interface can be used to tell the Context that an additional data should be available as additional configuration section. So, the methods "hasConfigOf", "getConfigOf" and "setConfigOf" can be used to manipulate those data.


ConfigExtraInterface
--------------------

Class: [ConfigExtraInterface](/../../Config/ConfigExtraInterface.php)

Methods:
 * **getName** - Gets the unique string identifier of additional data.
 * **configureContext** - Modifies the context ([ConfigContext](../../Processor/Config/ConfigContext.php)) to make it possible to get required additional data.
 * **isInheritable** - The method indicates whether this config extra is applicable to nested configs.
 * **getCacheKeyPart** - Gets a string that should be used as a part of a cache key used by config providers.
 
 
As a simplest example take a look into [DescriptionConfigExtra](../../Processor/Config/ConfigContext/DescriptionsConfigExtra.php) - the extra configuration to add a human-readable descriptions of entities and its' fields. 

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
    public function isInheritable()
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

Both methods `getName` and `getCachedKeyPart` in most cases will returns the same value because it's applicable as unique identifier as well as a cache key part.
The method `configureContext` is empty because we do not need any modification inside the context.
The method `isInheritable` returns `True` because we need all nesting configs to know about our extra configuration.


But the only implementation of `ConfigExtraInterface` do not means it will be automatically added into context. So, to add extra configuration into Context the corresponding [processor](./processors.md#overview) should call `addConfigExtra` with new instance of corresponding extra configuration as an argument. Typically the instantiation of extra configuration is the responsibility of some processor from `initialization` group, but depending on actual needs it can be registered in any processor.


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

After the extra configuration have been added into Context we will need some another processor(s) which will take the responsibility to manipulate the Context in order to realize the logic it was designed for. So, as mentioned in first example, the extra configuration [DescriptionConfigExtra](../../Processor/Config/ConfigContext/DescriptionsConfigExtra.php) tells that a human-readable descriptions should be added. Do to it we have a couple of processors that adds a description for entity, fields and filters respectively into Context.

As an example, take a look into:

- definition - [processors.get_config.yml](../config/processors.get_config.yml)

```yaml
    oro_api.get_config.set_description_for_entity:
        class: Oro\Bundle\ApiBundle\Processor\Config\GetConfig\SetDescriptionForEntity
        arguments:
            - @oro_entity.entity_class_name_provider
            - @oro_entity_config.provider.entity
        tags:
            - { name: oro.api.processor, action: get_config, extra: definition&descriptions, priority: -200 }
```

- processor - [SetDescriptionForEntity](../../Processor/Config/GetConfig/SetDescriptionForEntity.php)

```php
<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Adds "label", "plural_label" and "description" attributes for the entity.
 */
class SetDescriptionForEntity implements ProcessorInterface
{
    /** @var EntityClassNameProviderInterface */
    protected $entityClassNameProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param EntityClassNameProviderInterface $entityClassNameProvider
     * @param ConfigProvider                   $entityConfigProvider
     */
    public function __construct(
        EntityClassNameProviderInterface $entityClassNameProvider,
        ConfigProvider $entityConfigProvider
    ) {
        $this->entityClassNameProvider = $entityClassNameProvider;
        $this->entityConfigProvider    = $entityConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $entityClass = $context->getClassName();
        if (!$definition->hasLabel()) {
            $entityName = $this->entityClassNameProvider->getEntityClassName($entityClass);
            if ($entityName) {
                $definition->setLabel($entityName);
            }
        }
        if (!$definition->hasPluralLabel()) {
            $entityPluralName = $this->entityClassNameProvider->getEntityClassPluralName($entityClass);
            if ($entityPluralName) {
                $definition->setPluralLabel($entityPluralName);
            }
        }
        if (!$definition->hasDescription() && $this->entityConfigProvider->hasConfig($entityClass)) {
            $definition->setDescription(
                new Label($this->entityConfigProvider->getConfig($entityClass)->get('description'))
            );
        }
    }
}

```


ConfigExtraSectionInterface
---------------------------

Class: [ConfigExtraSectionInterface](../../Config/ConfigExtraSectionInterface.php).

Methods:
 * **getConfigType** - Gets the configuration type that can be loaded into this section.

