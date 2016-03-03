Configuration Reference
=======================

Table of Contents
-----------------
 - [Overview](#overview)
 - [Configuration structure](#configuration-structure)
 - [Config Extensions](#config-extensions)
 - [Config Sections](#config-sections)

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
php ./app/console oro:api:config:dump-reference --max-nesting-level=0
```

The default nesting level is 3. It's configured in parameter in [services.yml](../config/services.yml). So it will allow to easily change it via overriding the parameter. 

```yaml
parameters:
    # the maximum number of nesting target entities that can be specified in 'Resources/config/oro/api.yml'
    oro_api.config.max_nesting_level: 3
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

`oro_api.entities.exclude` flag excludes entity only from API, but in case an entity or its' field(s) should be excluded globally use `Resources/config/oro/entity.yml`, e.g.

```yaml
oro_entity:
    exclusions:
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity, field: fieldName }        # single field exclusion
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity, field: anotherFieldName } # single field exclusion
        - { entity: Acme\Bundle\AcmeBundle\Entity\AnotherAcmeEntity }                   # whole entity exclusion
```

Config Extensions
=================

-- ConfigExtensionRegistry(../../Config/ConfigExtensionRegistry.php)

 Registers the configuration extension.
 Returns all registered configuration extensions.
 Collects the configuration definition settings from all registered extensions.
 
How to add new config extension?
--------------------------------

Any config extension should:

- implement [ConfigExtensionInterface](../../Config/ConfigExtensionInterface.php). As an example see [TestConfigExtension](../../Tests/Unit/Config/Stub/TestConfigExtension.php)
- be registered in services.yml and marked with tag `oro_api.config_extension`, e.g.

```yaml
  acme_bundle.config_extension.my_config_extension:
    class: Acme\Bundle\AcmeBundle\ConfigExtension\MyConfigExtension
    public: false
    tags:
        - { name: oro_api.config_extension }
```

Config Sections
===============

const RELATIONS_SECTION  = 'relations';
const EXCLUSIONS_SECTION = 'exclusions';
const ENTITIES_SECTION   = 'entities';


describe the following config sections:

|Config Section | Description |
| ---                   | :--- |
|exclusions             | The section describes entity(ies) and\or field(s) exclusions | 
| ---                   |  |
|entities               | The section describes entities configurations |
|entities.entity        | The section describes whole single entity configuration with all fields, sorters, filters, etc. |
|entities.entity.fields | The section describes the configuration of fields per certain entity|
| ---                   | The relations configuration are similar to entity configuration|
|relations              | The section describes relations configurations |
|relations.entity       | The section describes whole single relation configuration with all fields, sorters, filters, etc. |
|relations.entity.fields| The section describes the configuration of fields per certain relation |
| ---                   |  |
|filters                | The section presents in both: entities and relations. Describes the configuration of filters |
|filters.fields         |  |
| ---                   |  |
|sorters                | The section presents in both: entities and relations. Describes the configuration of sorters |
|sorters.fields         |  |
| ---                   |  |



How to add new property into existing config section?
-----------------------------------------------------

TODO

How to deal with sections (add new, get existing one)?
------------------------------------------------------

TODO


- should be documented: 

-- ConfigExtraSectionInterface
  This interface can be used to tell the Context that an additional data should be available as additional type of configuration. 
  So, "hasConfigOf", "getConfigOf" and "setConfigOf" methods of the Context can be used to access those data. 

-- ConfigExtraInterface
  an interface for different kind requests for additional configuration data

-- ConfigLoaderInterface
 Loads a configuration from an array

-- ConfigLoaderFactory



TO DO
======

- example how to introduce "options" config section and add process "exclude_from" specific requests option
- also  how to do the same for a specific entity using a processor
