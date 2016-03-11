Configuration Reference
=======================

Table of Contents
-----------------
 - [Overview](#overview)
 - [Configuration structure](#configuration-structure)
    - ["Exclusions" configuration section & "exclude" flag](#exclusions-configuration-section-exclude-flag)
    - ["Entities" configuration section](#entities-configuration-section)
    - ["Relations" configuration section](#relations-configuration-section)
 - [Config Extensions](#config-extensions)
    - [How to add new configuration extension?](#how-to-add-new-configuration-extension)
    - [How to add new property into existing config section?](#how-to-add-new-property-into-existing-config-section)
    - [How to allow to extend section?](#how-to-allow-to-extend-section)
    - [Pre/PostProcessCallbacks in ConfigExtensionInterface](#prepostprocesscallbacks-in-configextensioninterface)
    - [Config loaders and ConfigExtraSectionInterface](#config-loaders-and-configextrasectioninterface)

Overview
========

The configuration declares all aspects related to specific entity. The certain configuration for entity should be placed in `api.yml` to be automatically loaded. Typically configuration starts from the root element `oro_api` and followed by configuration section and its properties. Each file can have single entity configuration or a collection of configurations for many entities.

To get the overall configuration structure, execute the following command:

```bash
php ./app/console oro:api:config:dump-reference
```

The default nesting level is 3, so please use the option `--max-nesting-level` to simplify the output, e.g.
  
```bash
php ./app/console oro:api:config:dump-reference --max-nesting-level=0
```

It's configured in parameter in [services.yml](../config/services.yml). So, if needed, it will allow to easily be changed by overriding the parameter `oro_api.config.max_nesting_level`. 

```yaml
parameters:
    # the maximum number of nesting target entities that can be specified in 'Resources/config/oro/api.yml'
    oro_api.config.max_nesting_level: 3
```

Configuration structure
=======================

All entities, except custom entities, dictionaries and enumerations are not accessible through Data API. To allow usage of an entity in Data API you can use `Resources/config/oro/api.yml` file. For example, to make `Acme\Bundle\ProductBundle\Product` entity available through Data API you can write the following configuration:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product: ~
```

The first level sections of configuration are:

* **exclusions** - describes exclusion rules per whole entity or certain entity field. This can be useful for example to exclude security specific data from being accessible via Data API.  
* **entities**   - describes the entities configuration
* **relations**  - describes the relations configuration

And a simplified configuration example:

```yaml
oro_api:
    exclusions:
        ...
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                ...
            sorters:
                fields:
                    ...
            filters:
                fields:
                    ...
            exclude: ~
        ...
    relations:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                ...
            sorters:
                fields:
                    ...
            filters:
                fields:
                    ...
        ...
```

"Exclusions" configuration section & "exclude" flag
---------------------------------------------------

The `exclusions` configuration section describes whether whole entity or some of its fields should be excluded from result. The definition is a key-value collection where `entity` keys' value is a FQCN of entity to be excluded and `field` keys' value is a field name of an entity to be excluded. If `field` is omitted it will lead to exclusion of whole entity.  

As an example:

```yaml
oro_api:
    exclusions:
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity0 }                 # whole entity exclusion
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity1, field: fieldName0 }  # field0 will be excluded
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity1, field: fieldName1 }  # field1 will be excluded
```

The same behaviour can be reached by usage of `exclude` flag under `entities` configuration section, e.g.

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity0:
            exclude: true
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity1:
            fields:
                fieldName0:
                    exclude: true
                fieldName1:
                    exclude: true
```

The mentioned flag can be also used to indicate whether sorter or filter for certain field should be excluded. Please note that exclusion on `fields` level (as mentioned above) has higher priority then `sorters` or `filters`, so it's not possible to exclude field without affecting its' sorters and filters. 

, e.g.

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity1:
            sorter:
                fields:
                    fieldName0:
                        exclude: true
            filters:
                fields:
                    fieldName1:
                        exclude: true
```

Please note  `oro_api.exclusions` rules will excludes entity only from Data API, but in case an entity or its' field(s) should be excluded globally use `Resources/config/oro/entity.yml`, e.g.

```yaml
oro_entity:
    exclusions:
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity, field: fieldName }        # single field exclusion
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity, field: anotherFieldName } # single field exclusion
        - { entity: Acme\Bundle\AcmeBundle\Entity\AnotherAcmeEntity }                   # whole entity exclusion
```

"Entities" configuration section
--------------------------------

The `entities` configuration section describe single or multiple entities configurations and contains options:

* **label** - String. A human-readable representation of the entity (used in auto generated api doc only)
* **plural_label** - String. A human-readable representation in plural of the entity (used in auto generated api doc only)
* **description** - String. A human-readable description of the entity (used in auto generated api doc only)
* **inherit** - Boolean. By default `true`. The flag indicates that the configuration for certain entity should be merged with parent entity configuration. So it allows to have huge configuration for some AbstractEntity and specifying and more simplified configurations for all its' inheritors. Or if an inherited entity should have completely different configuration and merging with parent configuration is not needed the flag should be set to `false`. 
* **exclusion_policy** - By default `none`. Can be "all" or "none". Indicates the type of the exclusion strategy that should be used for the entity. If is set to "all" - means that the configuration of all fields and associations was completed. 
* **disable_partial_load** - Boolean. By default `false`. The flag indicates whether usage of Doctrine partial object is disabled. It can be helpful for entities with SINGLE_TABLE inheritance mapping.
* **max_results** - Integer. By default unlimited. The maximum number of items in the result. Set -1 (it means unlimited), zero or positive value to set own limit. In JSON API the default is `10` - setting up by [SetDefaultPaging](../../Processor/GetList/JsonApi/SetDefaultPaging.php) processor.
* **order_by** - Array[[fieldName: ASC|DESC], ...]. The property can be used to configure default ordering.
* **hints** - Array[HINT_NAME0, [name: HINT_NAME1], [name: HINT_NAME2, value: FQCN], ...]. By default empty. Sets [Doctrine query hints](http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html#query-hints). The `name` is required  and `value` can be omitted.
* **post_serialize** - A handler to be used to modify serialized data [class: FQCN, method: methodName]

And an example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            label:                "Acme Entity"
            plural_label:         "Acme Entities"
            description:          "Acme Entities description"
            inherit:              false 
            exclusion_policy:     all 
            disable_partial_load: false 
            max_results:          25
            order_by:
                fieldName0:       DESC
                fieldName1:       ASC
            hints:
                - HINT_TRANSLATABLE
                - { name: HINT_FILTER_BY_CURRENT_USER }
                - { name: HINT_CUSTOM_OUTPUT_WALKER, value: "Acme\Bundle\AcmeBundle\AST_Walker_Class"}
            post_serialize:       [class: "Acme\Bundle\AcmeBundle\Serializer\MySerializerHandler", method: "serialize"]
            excluded:             false
            fields:
                ...
            filters:
                ...
            sorters:
                ...
```

* **fields** - the `fields` configuration section describes entity fields' configuration. Each item under `fields` starts from a field name and has properties:

* **label** - String. A human-readable representation of the field
* **description** - String. A human-readable description of the field
* **property_path** - String. Property path to reach the fields' value. Can be used for example if field and association names are not correspond. So, for example:
    
```yaml
fields:
    address:
        property_path: address.addressName
]
```

in this case the field `address` will contain the value from field `address_name`.

* **data_transformer** - The data transformer(s) to be applies to the field value. Can be specified as service name, array of service names or as FQCN and method name.

```yaml
fields
    fieldName0:
        data_transformer: my.data.transformer.service.id
    fieldName1:
        data_transformer:
            - my.data.transformer.service.id
            - { class: 'Acme\Bundle\AcmeBundle\DataTransformer\MyDataTransformer', method: 'transform' }       
    fieldName2:
        data_transformer: [my.data.transformer.service.id, { class: 'Acme\Bundle\AcmeBundle\DataTransformer\MyDataTransformer', method: 'transform' } ]
```

* **collapse** - Boolean. Associations ONLY. Indicates whether the entity should be collapsed. It means that target entity should be returned as a value, instead of an array with values of entity fields. Usually this property is set by "get_relation_config" processors to get identifier of the related entity.
* **exclude** - Boolean. Indicates whether the field should be excluded. This property is described above in [Exclusions" configuration section & "exclude" flag](#exclusions-configuration-section-exclude-flag).

The example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                name:
                    label:            "Acme name"
                    description:      "Acme name description"
                    property_path:    "firstName"
                enabled: null
                users:
                    collapse:         true
                    exclusion_policy: all
                    fields:
                        id: null
```

* **filters** - the `filters` configuration section describes the filtering possibilities. It contains two main properties: `exclusion_policy` and `fields`.
    * **exclusion_policy** - The `exclusion_policy` option works the same way as for `entities` section. Please refer to ["Entities" configuration section](#entities-configuration-section).
    * **fields** - just a container with fields' filters configuration, the `key` is a field name and `properties` are: 
        * **description** - String. A human-readable description of the fields' filter
        * **exclude** - Boolean. Indicates whether the field filter should be excluded. This property is described above in [Exclusions" configuration section & "exclude" flag](#exclusions-configuration-section-exclude-flag).
        * **property_path** - Property path to reach the fields' value. The same way as above in `fields` configuration section.
        * **data_type** - String. The data type of the filter value: boolean, integer, string, etc.
        * **allow_array** - Boolean. By default `false`. A flag indicates whether the filter value can be an array.
        * **default_value** - The default value for the filter.

The example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                ...
            filters:
                exclusion_policy: all
                fields:
                    id:
                        data_type: integer
                        exclude: true
                    name:
                        data_type: string
                        property_path: firstName
                        description: "My filter description"
                    enabled:
                        data_type: boolean
                        allow_array: false
                        default_value: true
```
    
* **sorters** - the `sorters` configuration section describes the sorting possibilities. It contains two main properties: `exclusion_policy` and `fields`.
    * **exclusion_policy** - The `exclusion_policy` option works the same way as for `entities` section. Please refer to ["Entities" configuration section](#entities-configuration-section).
    * **fields** - just a container with fields' sorters configuration, the `key` is a field name and `properties` are:
        * **exclude** - Boolean. Indicates whether the field sorter should be excluded. This property is described above in [Exclusions" configuration section & "exclude" flag](#exclusions-configuration-section-exclude-flag).
        * **property_path** - Property path to reach the fields' value. The same way as above in `fields` configuration section.

In other words the `sorters` section will just enable/disable sorting possibility per certain field. It do not has any responsibility for default sorting. 

The example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                ...
            filters:
                ...
            sorters:
                exclusion_policy: none
                fields:
                    id: ~
                    name:
                        property_path: firstName
                    enabled:
                        exclude: true
```

"Relations" configuration section
---------------------------------

The `relations` configuration describes how the entity data should be shown then retrieved as a relation to some other entity. It's absolutely identical to `entities` configuration section, the only difference is `exclude` flag - it's not available under relation configuration.


Config Extensions
=================

To deal with extensions it's easy to use [ConfigExtensionRegistry](../../Config/ConfigExtensionRegistry.php). It allows to:
- Registers the configuration extension.
- Returns all registered configuration extensions.
- Collects the configuration definition settings from all registered extensions.

Below is an overview table of sections and its typical childes. 

|Config Section | Description |
| --- | :--- |
|exclusions | The section describes entity(ies) and\or field(s) exclusions | 
|||
|entities               | The section describes entities configurations |
|entities.entity        | The section describes whole single entity configuration with all fields, sorters, filters, etc. |
|entities.entity.fields | The section describes the configuration of fields per certain entity|
|                       | The relations configuration are similar to entity configuration |
|relations              | The section describes relations configurations |
|relations.entity       | The section describes whole single relation configuration with all fields, sorters, filters, etc. |
|relations.entity.fields| The section describes the configuration of fields per certain relation |
|||
|entities/relations.entity.filters | The section can be present under both: entities and relations. Describes the configuration of filters |
|entities/relations.entity.filters.fields | The section describes the filter configuration per fields |
|||
|entities/relations.entity.sorters | The section can be present under both: entities and relations. Describes the configuration of sorters |
|entities/relations.entity.sorters.fields | The section describes the sorters configuration per fields |
|||

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

And to check that all works fine just execute the `oro:api:config:dump acmeentity --extra="Acme\Bundle\AcmeBundle\ConfigExtension\MyConfigExtension\TestConfigurationExtra"` command. The output will looks like this:

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

At this point we have newly created section with own properties and possibility to pass new configuration via yaml files. Please refer to [actions](./actions.md#context_class) documentation section for more detail about how to use configuration in Data API logic.
