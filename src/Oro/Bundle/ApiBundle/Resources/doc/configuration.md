Configuration Reference
=======================

Table of Contents
-----------------
 - [Overview](#overview)
 - [Configuration structure](#configuration-structure)
 - [Config Sections](#config-sections)
 - [Config Extensions](#config-extensions)
 - [Conclusion](#conclusion)

Overview
========

All entities, except custom entities, dictionaries and enumerations are not accessible through Data API. To allow usage of an entity in Data API you can use `Resources/config/oro/api.yml` file. For example, to make `Acme\Bundle\ProductBundle\Product` entity available through Data API you can write the following configuration:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product: ~
```

Configuration structure
=======================

To get the configuration structure, execute the following command:

```bash
php ./app/console oro:api:config:dump-reference
```

The result of the command execution will be output similar to this:

```yaml
# The structure of "Resources/config/oro/api.yml"
oro_api:
    exclusions:                                                # SECTION name
        entity:               ~ # Required                     # Fully-Qualified Class Name (FQCN)
        field:                ~                                # field name

    entities:                                                  # SECTION name
        # Prototype                                            # --------------------------------
        name:                                                  # Fully-Qualified Class Name (FQCN)
            inherit:              ~                            # 
            exclusion_policy:     ~                            # One of "all"; "none". A type of the exclusion strategy that should be used for the entity
            disable_partial_load: ~                            # a flag indicates whether usage of Doctrine partial object is disabled
            order_by:                                          # the ordering of the result
                # Prototype                                    # --------------------------------
                name:             ~                            # a field name and the value one of "ASC"; "DESC"
            max_results:          ~                            # the maximum number of items in the result
            hints:                                             # Doctrine query hints
                name:             ~                            # - please refer to [Doctine documentation](http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html#query-hints) for more details
                value:            ~                            #
            post_serialize:       ~                            # a handler to be used to modify serialized data
            label:                ~                            # a human-readable representation of the entity
            plural_label:         ~                            # a human-readable representation in plural of the entity
            description:          ~                            # a human-readable description of the entity

            fields:                                            # SECTION name
                # Prototype                                    # --------------------------------
                name:                                          # a field name
                    exclude:              ~                    # indicates whether the exclusion flag is set explicitly 
                    property_path:        ~                    # property path to reach the field value
                    collapse:             ~                    # associations ONLY. Indicates whether the collapse target entity flag is set explicitly  
                    data_transformer:     ~                    # the data transformer(s) to be applies to the field value
                    label:                ~                    # human-readable representation of the field
                    description:          ~                    # human-readable description of the field

            filters:                                           # SECTION name
                exclusion_policy:         ~                    # One of "all"; "none"
                fields:                                        # Represents a filter configuration per field
                    # Prototype                                # --------------------------------
                    name:                                      # a field name
                        exclude:          ~                    # flag indicates whether the field should be excluded
                        property_path:    ~                    # property path to reach the field value
                        data_type:        ~                    # data type of the filter value
                        allow_array:      ~                    # flag indicates whether the filter value can be an array or not
                        default_value:    ~                    # default value for the filter
                        description:      ~                    # human-readable description of the filter

            sorters:                                           # SECTION name
                exclusion_policy:         ~                    # One of "all"; "none"
                fields:                                        # Represents a sorter configuration per field
                    # Prototype                                # --------------------------------
                    name:                                      # a field name
                        exclude:          ~                    # flag indicates whether the field should be excluded
                        property_path:    ~                    # property path to reach the field value

            exclude:                      ~                    # flag indicates whether entity should be excluded

    relations:                                                 # The relation configuration is similar to entity
        # Prototype                                            # configuration except it does not have `exclude` property.
        name:                                                  #
            inherit:              ~
            exclusion_policy:     ~ # One of "all"; "none"
            disable_partial_load: ~
            order_by:
                # Prototype
                name:             ~
            max_results:          ~
            hints:
                name:             ~
                value:            ~
            post_serialize:       ~
            collapse:             ~
            fields:
                # Prototype
                name:
                    exclude:              ~
                    property_path:        ~
                    collapse:             ~
                    data_transformer:     ~
                    label:                ~
                    description:          ~
            filters:
                exclusion_policy:         ~ # One of "all"; "none"
                fields:
                    # Prototype
                    name:
                        exclude:          ~
                        property_path:    ~
                        data_type:        ~
                        allow_array:      ~
                        default_value:    ~
                        description:      ~
            sorters:
                exclusion_policy:         ~ # One of "all"; "none"
                fields:
                    # Prototype
                    name:
                        exclude:          ~
                        property_path:    ~
```

Tips and tricks
---------------

--
`oro:api:config:dump-reference` command shows the configuration. And the default nesting level is 3. It's configured in parameter in [services.yml](../config/services.yml). So it will allow to easily change it via overriding the parameter. 

```yaml
parameters:
    # the maximum number of nesting target entities that can be specified in 'Resources/config/oro/api.yml'
    oro_api.config.max_nesting_level: 3
```

--
`oro_api.entities.exclude` flag excludes entity only from API, but in case an entity or its' field(s) should be excluded globally use `Resources/config/oro/entity.yml`, e.g.

```yaml
oro_entity:
    exclusions:
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity, field: fieldName }        # single field exclusion
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity, field: anotherFieldName } # single field exclusion
        - { entity: Acme\Bundle\AcmeBundle\Entity\AnotherAcmeEntity }                   # whole entity exclusion
```

Config Sections
===============

|Config Section         | Description |
| ---                   | :--- |
|exclusions             | The section describes entity(ies) and\or field(s) exclusions | 
| ---                   | --- |
|entities               | The section describes entities configurations |
|entities.entity        | The section describes whole single entity configuration with all fields, sorters, filters, etc. |
|entities.entity.fields | The section describes the configuration of fields per certain entity|
| ---                   | The relations configuration are similar to entity configuration|
|relations              | The section describes relations configurations |
|relations.entity       | The section describes whole single relation configuration with all fields, sorters, filters, etc. |
|relations.entity.fields| The section describes the configuration of fields per certain relation |
| ---                   | --- |
|filters                | The section presents in both: entities and relations. Describes the configuration of filters |
|filters.fields         | The section describes the filter configuration per fields |
| ---                   | --- |
|sorters                | The section presents in both: entities and relations. Describes the configuration of sorters |
|sorters.fields         | The section describes the sorters configuration per fields |
| ---                   | --- |


Config Extensions
=================

To deal with extensions it's easy to use [ConfigExtensionRegistry](../../Config/ConfigExtensionRegistry.php). It allows to:
- Registers the configuration extension.
- Returns all registered configuration extensions.
- Collects the configuration definition settings from all registered extensions.

 - [How to add new configuration extension?](#how-to-add-new-configuration-extension)
 - [How to add new property into existing config section?](#how-to-add-new-property-into-existing-config-section)
 - [How to allow to extend section?](#how-to-allow-to-extend-section)
 - [Pre/PostProcessCallbacks in ConfigExtensionInterface](#prepostprocesscallbacks-in-configextensioninterface)
 - [Config loaders and ConfigExtraSectionInterface](#config-loaders-and-configextrasectioninterface)


How to add new configuration extension?
---------------------------------------

At first any configuration extension should:

- be registered in services.yml and marked with tag `oro_api.config_extension`
- implement [ConfigExtensionInterface](../../Config/ConfigExtensionInterface.php) or extend [AbstractConfigExtension](../../Config/AbstractConfigExtension)


```yaml
  acme_bundle.config_extension.my_config_extension:
    class: Acme\Bundle\AcmeBundle\ConfigExtension\MyConfigExtension
    public: false
    tags:
        - { name: oro_api.config_extension }
```

```php
namespace Acme\Bundle\AcmeBundle\ConfigExtension\MyConfigExtension;

class MyConfigExtension implements ConfigExtensionInterface
{
    public function getEntityConfigurationSections()
    {
        return [];
    }

    public function getConfigureCallbacks()
    {
        return [];
    }

    public function getPreProcessCallbacks()
    {
        return [];
    }

    public function getPostProcessCallbacks()
    {
        return [];
    }

    public function getEntityConfigurationLoaders()
    {
        return [];
    }
}
```

The next step is the configuration class that will implement [ConfigurationSectionInterface](../../Config/Definition/ConfigurationSectionInterface.php), e.g.

```
namespace Acme\Bundle\AcmeBundle\ConfigExtension\MyConfigExtension;

class TestConfiguration implements ConfigurationSectionInterface
{
    public function configure(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
}
```

after that the method `getEntityConfigurationSections` in `MyConfigExtension` needs changes a bit and will looks like this:

```
    public function getEntityConfigurationSections()
    {
        return ['test_section' => new TestConfiguration()];
    }
```

so the new section `test_section` will appear in the `oro:api:config:dump-reference` command execution output

```yaml
# The structure of "Resources/config/oro/api.yml"
oro_api:
    ...
    entities:
        name:
            ...
            test_section:         []
            ...
```

How to add new property into existing config section?
-----------------------------------------------------

So, in the previous step we have added a new section called `test_section`. Let's add a new property.
To deal with it all that we have to do is just modify the `configure` method in `TestConfiguration` class, e.g.

```
    public function configure(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        $node->scalarNode('test_property')->cannotBeEmpty()->end();
    }
```

And the `oro:api:config:dump-reference` command execution output

```yaml
# The structure of "Resources/config/oro/api.yml"
oro_api:
    ...
    entities:
        name:
            ...
            test_section:
                test_property: ~
            ...
```

How to allow to extend section?
-------------------------------

Let's extend the previous examples to allow extending the section from another bundles.

```php

namespace Acme\Bundle\AcmeBundle\ConfigExtension\MyConfigExtension;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

use Oro\Bundle\ApiBundle\Config\Definition\AbstractConfigurationSection;
use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSectionInterface;

class TestConfiguration extends AbstractConfigurationSection implements ConfigurationSectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        $sectionName = 'test_section';

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        $parentNode
            //->ignoreExtraKeys(false) @todo: uncomment after migration to Symfony 2.8+
            ->beforeNormalization()
            ->always(
                function ($value) use ($preProcessCallbacks, $sectionName) {
                    return $this->callProcessConfigCallbacks($value, $preProcessCallbacks, $sectionName);
                }
            );

        $node->scalarNode('test_property')->cannotBeEmpty()->end();

        $this->callConfigureCallbacks($node, $configureCallbacks, $sectionName);

        $parentNode
            ->validate()
            ->always(
                function ($value) use ($postProcessCallbacks, $sectionName) {
                    return $this->callProcessConfigCallbacks($value, $postProcessCallbacks, $sectionName);
                }
            );
    }
}
```

So after that it will be possible to add new property from any other extension, e.g.

```php

namespace Acme\Bundle\AnotherAcmeBundle\ConfigExtension\MyNewConfigExtension;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class AnotherTestConfigExtension implements ConfigExtensionInterface
{
    public function getConfigureCallbacks()
    {
        return [
            'test_section' => function (NodeBuilder $node) {
                $node->scalarNode('test_property_new');
            },
        ];
    }

```

And the `oro:api:config:dump-reference` command execution output

```yaml

# The structure of "Resources/config/oro/api.yml"
oro_api:
    ...
    entities:
        name:
            ...
            test_section:
                test_property_new: ~
                test_property: ~
            ...
```

Pre/PostProcessCallbacks in ConfigExtensionInterface
----------------------------------------------------

When the configs are processed they are first normalized, then merged and finally the tree is used to validate the result. So, the `getPreProcessCallbacks` methods will run before normalization and `getPostProcessCallbacks` will be used to validate. As an example see [TargetEntityDefinitionConfiguration](../../Config/Definition/TargetEntityDefinitionConfiguration.php), e.g.

```
    ...
    protected function postProcessConfig(array $config)
    {
        if (empty($config[EntityDefinitionConfig::ORDER_BY])) {
            unset($config[EntityDefinitionConfig::ORDER_BY]);
        }
        if (empty($config[EntityDefinitionConfig::HINTS])) {
            unset($config[EntityDefinitionConfig::HINTS]);
        }
        if (empty($config[EntityDefinitionConfig::POST_SERIALIZE])) {
            unset($config[EntityDefinitionConfig::POST_SERIALIZE]);
        }
        if (empty($config[EntityDefinitionConfig::FIELDS])) {
            unset($config[EntityDefinitionConfig::FIELDS]);
        }

        return $config;
    }
    ...
```

Here's, the main idea is to cleanup empty sections in configuration.


Config loaders and ConfigExtraSectionInterface
----------------------------------------------

Configuration loaders are represented with [ConfigLoaderInterface](../../Config/ConfigLoaderInterface.php) and [ConfigLoaderFactory](../../Config/ConfigLoaderFactory.php).

ConfigLoaderInterface - Loads a configuration from an array
ConfigLoaderFactory are used to:
  - Determinate whether a loader for a given configuration type exists.
  - Find the loader that can be used to load a given configuration type.
  - Register or override the loader for a given configuration type.

ConfigExtraSectionInterface
  The interface used to tell the Context that an additional data should be available as additional type of configuration. 
  So, "hasConfigOf", "getConfigOf" and "setConfigOf" methods of the Context can be used to access those data. 


So, the responsibility of Config loaders is to pass configuration options from array(yaml) into objects. For example, we want to set values for our newly created properties for some entity, lets say `AcmeEntity`:

Defining properties in `api.yml`: 

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            test_section:
                test_property:     "test value"
                test_property_new: "another test value"
            ...
```

the Config:

```php
namespace Acme\Bundle\AcmeBundle\ConfigExtension\MyConfigExtension;

use Oro\Bundle\ApiBundle\Config\Traits;

class TestConfig
{
    use Traits\ConfigTrait;

    const PROP1 = 'test_property';
    const PROP2 = 'test_property_new';

    /** @var array */
    protected $items = [];

    public function toArray()
    {
        return $this->items;
    }

    public function isEmpty()
    {
        return empty($this->items);
    }
```

If it needs to have own setters/getters, just add them


```php
    public function getTestProperty()
    {
        return array_key_exists(self::PROP1, $this->items)
            ? $this->items[self::PROP1]
            : null;
    }
    public function getTestPropertyNew()
    {
        return array_key_exists(self::PROP2, $this->items)
            ? $this->items[self::PROP2]
            : null;
    }
    public function setTestProperty($value)
    {
        if ($value) {
            $this->items[self::PROP1] = $value;
        } else {
            unset($this->items[self::PROP1]);
        }
    }
    public function setTestPropertyNew($value)
    {
        if ($value) {
            $this->items[self::PROP2] = $value;
        } else {
            unset($this->items[self::PROP2]);
        }
    }
```

the Config loader:


```php
namespace Acme\Bundle\AcmeBundle\ConfigExtension\MyConfigExtension;

class TestConfigurationLoader extends AbstractConfigLoader implements ConfigLoaderInterface
{
    public function load(array $config)
    {
        $testConfig = new TestConfig();
        foreach ($config as $key => $value) {
            $this->setValue($testConfig, $key, $value);
        }
        return $testConfig;
    }
}
```

or if it needs to use own setters:

```php
class TestConfigurationLoader extends AbstractConfigLoader implements ConfigLoaderInterface
{
    /** @var array */
    protected $methodMap = [
        TestConfig::PROP1 => 'setTestProperty',
        TestConfig::PROP2 => 'setTestPropertyNew'
    ];

    public function load(array $config)
    {
        $testConfig = new TestConfig();

        foreach ($config as $key => $value) {
            if (isset($this->methodMap[$key])) {
                $this->callSetter($testConfig, $this->methodMap[$key], $value);
            } else {
                $this->setValue($testConfig, $key, $value);
            }
        }
        return $testConfig;
    }
}
```

the next step is implementation of ConfigExtraSectionInterface


```php
namespace Acme\Bundle\AcmeBundle\ConfigExtension\MyConfigExtension;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class TestConfigurationExtra implements ConfigExtraInterface, ConfigExtraSectionInterface
{
    const NAME = 'test_section';

    public function getName()
    {
        return self::NAME;
    }

    public function configureContext(ConfigContext $context)
    {
    }

    public function isInheritable()
    {
        return true;
    }

    public function getConfigType()
    {
        return self::NAME;
    }

    public function getCacheKeyPart()
    {
        return self::NAME;
    }
}
```

And to check that all works fine just execute the `oro:api:config:dump acmeentity --with-extras="Acme\Bundle\AcmeBundle\ConfigExtension\MyConfigExtension\TestConfigurationExtra"` command. The output will looks like this:


```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            exclusion_policy: all
            fields:
                id: ~
                ...
            test_section:
                test_property:     "test value"
                test_property_new: "another test value"
```

Conclusion
==========

At this point we have newly created section with own properties and possibility to pass new configuration via yaml files. Please refer to [processors](processors.md) documentation section for more detail about how to use configuration in Data API logic.
