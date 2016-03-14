Configuration Reference
=======================

Table of Contents
-----------------
 - [Overview](#overview)
 - [Configuration structure](#configuration-structure)
 - ["exclusions" configuration section & "exclude" flag](#exclusions-configuration-section--exclude-flag)
 - ["entities" configuration section](#entities-configuration-section)
 - ["relations" configuration section](#relations-configuration-section)
 - [ConfigExtraSectionInterface](#configextrasectioninterface)

Overview
--------

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
-----------------------

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

"exclusions" configuration section & "exclude" flag
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

"entities" configuration section
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
* **exclude** - Boolean. Indicates whether the field should be excluded. This property is described above in ["exclusions" configuration section & "exclude" flag](#exclusions-configuration-section-exclude-flag).

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
    * **exclusion_policy** - The `exclusion_policy` option works the same way as for `entities` section. Please refer to ["entities" configuration section](#entities-configuration-section).
    * **fields** - just a container with fields' filters configuration, the `key` is a field name and `properties` are:
        * **description** - String. A human-readable description of the fields' filter
        * **exclude** - Boolean. Indicates whether the field filter should be excluded. This property is described above in ["exclusions" configuration section & "exclude" flag](#exclusions-configuration-section-exclude-flag).
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
    * **exclusion_policy** - The `exclusion_policy` option works the same way as for `entities` section. Please refer to ["entities" configuration section](#entities-configuration-section).
    * **fields** - just a container with fields' sorters configuration, the `key` is a field name and `properties` are:
        * **exclude** - Boolean. Indicates whether the field sorter should be excluded. This property is described above in ["exclusions" configuration section & "exclude" flag](#exclusions-configuration-section-exclude-flag).
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

"relations" configuration section
---------------------------------

The `relations` configuration section describes a configuration of an entity if it is used in a relationship. This section is absolutely identical to the [entities](#entities-configuration-section) section, the only difference is the `exclude` flag for an entity - it's not available under a configuration of a relation.

ConfigExtraSectionInterface
---------------------------

ConfigExtraSectionInterface
  The interface used to tell the Context that an additional data should be available as additional type of configuration.
  So, "hasConfigOf", "getConfigOf" and "setConfigOf" methods of the Context can be used to access those data.


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

At this point we have newly created section with own properties and possibility to pass new configuration via yaml files. Please refer to [actions](./actions.md#context-class) documentation section for more detail about how to use configuration in Data API logic.
