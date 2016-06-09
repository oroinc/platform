Configuration Reference
=======================

Table of Contents
-----------------
 - [Overview](#overview)
 - [Configuration structure](#configuration-structure)
 - ["exclude" option](#exclude-option)
 - ["entities" configuration section](#entities-configuration-section)
 - ["relations" configuration section](#relations-configuration-section)
 - ["actions" configuration section](#actions-configuration-section)

Overview
--------

The configuration declares all aspects related to specific entity. The  configuration should be placed in `Resources/config/oro/api.yml` to be automatically loaded.

All entities, except custom entities, dictionaries and enumerations are not accessible through Data API. To allow usage of an entity in Data API you have to enable it directly. For example, to make `Acme\Bundle\ProductBundle\Product` entity available through Data API you can write the following configuration:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product: ~
```

Configuration structure
-----------------------

To get the overall configuration structure, execute the following command:

```bash
php app/console oro:api:config:dump-reference
```

By default this command shows configuration of nesting entities. To simplify the output you can use the `--max-nesting-level` option, e.g.

```bash
php app/console oro:api:config:dump-reference --max-nesting-level=0
```

The default nesting level is `3`. It is specified in [services.yml](../config/services.yml) via the `oro_api.config.max_nesting_level` parameter. So, if needed, you can easily change this value.

```yaml
parameters:
    # the maximum number of nesting target entities that can be specified in 'Resources/config/oro/api.yml'
    oro_api.config.max_nesting_level: 3
```

The first level sections of configuration are:

* [entities](#entities-configuration-section)   - describes the configuration of entities.
* [relations](#relations-configuration-section)  - describes the configuration of relationships.

Top level configuration example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            exclude:
            ...
            fields:
                ...
            filters:
                fields:
                    ...
            sorters:
                fields:
                    ...
            actions:
                ...
        ...
    relations:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                ...
            filters:
                fields:
                    ...
            sorters:
                fields:
                    ...
        ...
```

"exclude" option
----------------

The `exclude` configuration option describes whether an entity or some of its fields should be excluded from Data API.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity1:
            exclude: true # exclude the entity from Data API
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity2:
            fields:
                field1:
                    exclude: true # exclude the field from Data API
```

Also the `exclude` option can be used to indicate whether filtering or sorting for certain field should be disabled. Please note that filtering and sorting for the excluded field are disabled automatically, so it's not possible to filter or sort by excluded field.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity1:
            sorter:
                fields:
                    field1:
                        exclude: true
            filters:
                fields:
                    field1:
                        exclude: true
```

Please note that `exclude` option are applicable only for Data API. In case if an entity or its' field(s) should be excluded globally use `Resources/config/oro/entity.yml`, e.g.:

```yaml
oro_entity:
    exclusions:
        # whole entity exclusion
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity1 }
        # exclude field1 of Acme\Bundle\AcmeBundle\Entity\Entity2 entity
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity2, field: field1 }
```

"entities" configuration section
--------------------------------

The `entities` section describes a configuration of entities.

Each entity can have next properties:

* **label** *string* A human-readable representation of the entity. Used in auto generated documentation only.
* **plural_label** *string* A human-readable representation in plural of the entity. Used in auto generated documentation only.
* **description** *string* A human-readable description of the entity. Used in auto generated documentation only.
* **inherit** *boolean* By default `true`. The flag indicates that the configuration for certain entity should be merged with the configuration of a parent entity. If a derived entity should have completely different configuration and merging with parent configuration is not needed the flag should be set to `false`.
* **exclusion_policy** *string* - Can be `all` or `none`. By default `none`. Indicates the exclusion strategy that should be used for the entity. `all` means that all fields are not configured explicitly will be excluded. `none` means that only fields marked with `exclude` flag will be excluded.
* **disable_partial_load** *boolean* The flag indicates whether usage of Doctrine partial objects is disabled. By default `false`. It can be helpful for entities with table inheritance mapping.
* **max_results** *integer* The maximum number of entities in the result. Set -1 (it means unlimited), zero or positive value to set the limit. Can be used to set the limit for both root and related entities.
* **order_by** *array* The property can be used to configure default ordering. The item key is the name of a field. The value can be `ASC` or `DESC`.
* **hints** *array* Sets [Doctrine query hints](http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html#query-hints). Each item can be a string or an array with `name` and `value` keys. The string value is a short form of `[name: hint name]`.
* **post_serialize** *callable* A handler to be used to modify serialized data.
* **delete_handler** *string* The id of a service that should be used to delete entity by the [delete](./actions.md#delete-action) and [delete_list](./actions.md#delete_list-action) actions. By default the [oro_soap.handler.delete](../../../SoapBundle/Handler/DeleteHandler.php) service is used.

Example:

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
                field1: DESC
                field2: ASC
            hints:
                - HINT_TRANSLATABLE
                - { name: HINT_FILTER_BY_CURRENT_USER }
                - { name: HINT_CUSTOM_OUTPUT_WALKER, value: "Acme\Bundle\AcmeBundle\AST_Walker_Class"}
            post_serialize:       ["Acme\Bundle\AcmeBundle\Serializer\MySerializationHandler", "serialize"]
            delete_handler:       acme.demo.test_entity.delete_handler
            excluded:             false
            fields:
                ...
            filters:
                ...
            sorters:
                ...
```

* **form_type** *string* The form type that should be used for the entity in [create](./actions.md#create-action) and [update](./actions.md#update-action) actions. By default the `form` form type is used.
* **form_options** *array* The form options that should be used for the entity in [create](./actions.md#create-action) and [update](./actions.md#update-action) actions. By default the following options is set:

| Option Name | Option Value |
| --- | --- |
| data_class | The class name of the entity |
| validation_groups | ['Default', 'api'] |
| extra_fields_message | This form should not contain extra fields: "{{ extra_fields }}" |

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            form_type: acme_entity.api_form
            form_options:
                validation_groups: ['Default', 'api', 'my_group']
```

* **fields** - This section describes entity fields' configuration.

Each field can have next properties:

* **data_type** *string* The data type of the field value. Can be `boolean`, `integer`, `string`, etc.
* **label** *string* A human-readable representation of the field. Used in auto generated documentation only.
* **description** *string* A human-readable description of the field. Used in auto generated documentation only.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                field1:
                    data_type:   time
                    label:       "Acme name"
                    description: "Acme description"
```

* **property_path** *string* The property path to reach the fields' value. Can be used to rename a field or to access to a field of related entity.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            fields:
                # the "firstName" field will be renamed to the "name" field
                name:
                    property_path: firstName

                # the "addressName" field will contain the value of the "name" field of the "address" related entity
                addressName:
                    property_path: address.name

```

* **data_transformer** - The data transformer(s) to be applies to the field value. Can be specified as service name, array of service names or as FQCN and method name.

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            fields
                field1:
                    data_transformer: "my.data.transformer.service.id"
                field2:
                    data_transformer:
                        - "my.data.transformer.service.id"
                        - ["Acme\Bundle\AcmeBundle\DataTransformer\MyDataTransformer", "transform"]
```

* **collapse** *boolean* Indicates whether the entity should be collapsed. It is applicable for associations only. It means that target entity should be returned as a value, instead of an array with values of entity fields. Usually this property is set by [get_relation_config](./actions.md#get_relation_config-action) processors to get identifier of the related entity.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            fields:
                field1: # full syntax for "collapse" property
                    collapse:         true
                    exclusion_policy: all
                    fields:
                        targetField1: null
                field2: # short syntax for "collapse" property
                    fields: targetField1
```

* **exclude** *boolean* Indicates whether the field should be excluded. This property is described above in ["exclude" option](#exclude-option).

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                field1:
                    exclude: true
```

* **form_type** *string* The form type that should be used for the field in [create](./actions.md#create-action) and [update](./actions.md#update-action) actions.
* **form_options** *array* The form options that should be used for the field in [create](./actions.md#create-action) and [update](./actions.md#update-action) actions.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            ...
            fields:
                field1:
                    form_type: text
                    form_options:
                        trim: false
```

* **filters** - This section describes fields by which the result data can be filtered. It contains two properties: `exclusion_policy` and `fields`.
    * **exclusion_policy** *string* Can be `all` or `none`. By default `none`. Indicates the exclusion strategy that should be used. `all` means that all fields are not configured explicitly will be excluded. `none` means that only fields marked with `exclude` flag will be excluded.
    * **fields** This section describes a configuration of each field that can be used to filter the result data. Each filter can have the following properties:
        * **description** *string* A human-readable description of the filter. Used in auto generated documentation only.
        * **exclude** *boolean* Indicates whether filtering by this field should be disabled. By default `false`.
        * **property_path** *string* The property path to reach the fields' value. The same way as above in `fields` configuration section.
        * **data_type** *string* The data type of the filter value. Can be `boolean`, `integer`, `string`, etc.
        * **allow_array** *boolean* A flag indicates whether the filter can contains several values. By default `false`.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            filters:
                exclusion_policy: all
                fields:
                    field1:
                        data_type: integer
                        exclude: true
                    field2:
                        data_type: string
                        property_path: firstName
                        description: "My filter description"
                    field3:
                        data_type: boolean
                        allow_array: false
```

* **sorters** - This section describes fields by which the result data can be sorted. It contains two properties: `exclusion_policy` and `fields`.
    * **exclusion_policy** *string* Can be `all` or `none`. By default `none`. Indicates the exclusion strategy that should be used. `all` means that all fields are not configured explicitly will be excluded. `none` means that only fields marked with `exclude` flag will be excluded.
    * **fields** - This section describes a configuration of each field that can be used to sort the result data. Each sorter can have the following properties:
        * **exclude** *boolean* Indicates whether sorting by this field should be disabled. By default `false`.
        * **property_path** *string* The property path to reach the fields' value. The same way as above in `fields` configuration section.

Example:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            sorters:
                fields:
                    field1:
                        property_path: firstName
                    field2:
                        exclude: true
```

"relations" configuration section
---------------------------------

The `relations` configuration section describes a configuration of an entity if it is used in a relationship. This section is absolutely identical to the [entities](#entities-configuration-section) section, the only difference is the `exclude` flag for an entity - it's not available under this configuration section.

"actions" configuration section
-------------------------------

The `actions` configuration section allows to specify action-specific options. The options from this section will be added to the entity configuration. If an option exists in both entity and action configurations the action option wins. The exception is the `exclude` option. This option is used to disable an action for a specific entity and it is not copied to the entity configuration. Now `get`, `get_list` and `delete` actions are supported.

Each action can have next properties:

* **exclude** *boolean* Indicates whether the action is disabled for entity. By default `false`.
* **description** *string* The entity description for the action.
* **acl_resource** *string* The name of ACL resource that should be used to protect an entity in a scope of this action. The `null` can be used to disable access checks.
* **status_codes** *array* The possible response status codes for the action.
* **form_type** *string* The form type that should be used for the entity. This option overrides **form_type** option defined in ["entities" configuration section](#entities-configuration-section).
* **form_options** *array* The form options that should be used for the entity. These options override options defined in ["entities" configuration section](#entities-configuration-section).
* **fields** - This section describes entity fields' configuration specific for a particular action. These options override options defined in ["entities" configuration section](#entities-configuration-section).

Each field can have next properties:

* **exclude** *boolean* Indicates whether the field should be excluded for a particular action. This property is described above in ["exclude" option](#exclude-option).
* **form_type** *string* The form type that should be used for the field.
* **form_options** *array* The form options that should be used for the field.

By default, the following permissions are used to restrict access to an entity in a scope of the specific action:

| Action | Permission |
| --- | --- |
| get | VIEW |
| get_list | VIEW |
| delete | DELETE |
| delete_list | DELETE |


Examples of `actions` section configuration:

Disable `delete` action for an entity:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            actions:
                delete:
                    exclude: true
```

Also a short syntax can be used:
                
```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            actions:
                delete: false
```                      

Set custom ACL resource for the `get_list` action:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            actions:
                get_list:
                    acl_resource: acme_view_resource
```  

Turn off access checks for the `get` action:

```yaml
oro_api:
    entities:
       Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            actions:
                get:
                    acl_resource: ~
```

Add additional status code for `delete` action:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            actions:
                delete:
                    status_codes:
                        '417': 'Returned when expectations failed'
```

or

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            actions:
                delete:
                    status_codes:
                        '417':
                            description: 'Returned when expectations failed'
```

Remove existing status code for `delete` action:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            actions:
                delete:
                    status_codes:
                        '417': false
```

or

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            actions:
                delete:
                    status_codes:
                        '417':
                            exclude: true
```

Exclude a field for `update` action:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity:
            actions:
                update:
                    fields:
                        field1:
                            exclude: true
```
