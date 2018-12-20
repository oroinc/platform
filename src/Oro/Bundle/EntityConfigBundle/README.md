# OroEntityConfigBundle

OroEntityConfigBundle enables developers to define and configure the entities metadata in the YAML configuration files and provides the ability for the application users to set values of these metadata properties in the entity management UI.

## Getting Started

To show how metadata can be added to an entity lets add the following YAML file (this file must be located in `[BundleName]/Resources/config/oro/entity_config.yml`):
``` yaml
entity_config:
    acme:                                      # a configuration scope name
        entity:                                # a section describes an entity
            items:                             # starts a description of entity attributes
                demo_attr:                     # adds an attribute named 'demo_attr'
                    options:
                        default_value: 'Demo'  # sets the default value for 'demo_attr' attribute
```
This configuration adds 'demo_attr' attribute with 'Demo' value to all configurable entities. The configurable entity is an entity marked with `@Config` annotation. Also this code automatically adds a service named **oro_entity_config.provider.acme** into DI container. You can use this service to get a value of 'demo_attr' attribute for particular entity.
To apply this changes execute **oro:entity-config:update** command:
```bash
php bin/console oro:entity-config:update
```
An example how to get a value of a configuration attribute:
``` php
<?php
    /** @var ConfigProvider $acmeConfigProvider */
    $acmeConfigProvider = $this->get('oro_entity_config.provider.acme');

    // retrieve a value of 'demo_attr' attribute for 'AcmeBundle\Entity\SomeEntity' entity
    // the value of $demoAttr variable will be 'Demo'
    $demoAttr = $acmeConfigProvider->getConfig('AcmeBundle\Entity\SomeEntity')->get('demo_attr');
```
If you want to set a value different than the default one for some entity just write it in `@Config` annotation for this entity. For example:
``` php
<?php
/**
 * @ORM\Entity
 * @Config(
 *  defaultValues={
 *      "acme"={
 *          "demo_attr"="MyValue"
 *      }
 *  }
 * )
 */
class MyEntity
{
    ...
}
```

The result is demonstrated in the following code:
``` php
<?php
    /** @var ConfigProvider $acmeConfigProvider */
    $acmeConfigProvider = $this->get('oro_entity_config.provider.acme');

    // retrieve a value of 'demo_attr' attribute for 'AcmeBundle\Entity\SomeEntity' entity
    // the value of $demoAttr1 variable will be 'Demo'
    $demoAttr1 = $acmeConfigProvider->getConfig('AcmeBundle\Entity\SomeEntity')->get('demo_attr');

    // retrieve a value of 'demo_attr' attribute for 'AcmeBundle\Entity\MyEntity' entity
    // the value of $demoAttr2 variable will be 'MyValue'
    $demoAttr2 = $acmeConfigProvider->getConfig('AcmeBundle\Entity\MyEntity')->get('demo_attr');
```
Basically it is all you need to add metadata to any entity. But in most cases you want to allow an administrator to manage your attribute in UI. To accomplish this lets change our YAML file in the following way:
``` yaml
entity_config:
    acme:                                           # a configuration scope name
        entity:                                     # a section describes an entity
            items:                                  # starts a description of entity attributes
                demo_attr:                          # adds an attribute named 'demo_attr'
                    options:
                        default_value: 'Demo'       # sets the default value for 'demo_attr' attribute
                        translatable:  true         # means that value of this attribute is translation key
                                                    # and actual value should be taken from translation table
                                                    # or in twig via "|trans" filter
                        indexed:       true         # TRUE if an attribute should be filterable or sortable in a data grid
                    grid:                           # configure a data grid to display 'demo_attr' attribute
                        type:          string       # sets the attribute type
                        label:         'Demo Attr'  # sets the data grid column name
                        show_filter:   true         # the next three lines configure a filter for 'Demo Attr' column
                        filterable:    true
                        filter_type:   string
                        sortable:      true         # allows an administrator to sort rows clicks on 'Demo Attr' column
                    form:
                        type:          text         # sets the attribute type
                        options:
                            block:     entity       # specifies in which block on the form this attribute should be displayed
                            label:     'Demo Attr'  # sets the label name
```
Now you may go to System > Entities. The 'Demo Attr' column should be displayed in the grid. Click Edit on any entity to go to edit entity form. 'Demo Attr' field should be displayed there.

[Example of YAML config](Resources/doc/configuration.md)

## Indexed attributes

All configuration data are stored as a serialized array in `data` column of `oro_entity_config` and `oro_entity_config_field` tables for entities and fields appropriately. But sometime you need to get a value of some configuration attribute in SQL query. For example it is required for attributes visible in grids in System > Entities section and have a filter or allow sorting in this grid. In this case you can mark an attribute as indexed. For example:
``` yaml
entity_config:
    acme:
        entity:
            items:
                demo_attr:
                    options:
                        indexed: true
```
When you do this a copy of this attribute will be stored (and will be kept synchronized if a value is changed) in `oro_entity_config_index_value` table. As a result you can write SQL query like this:
``` sql
select *
from oro_entity_config c
    inner join oro_entity_config_index_value v on v.entity_id = c.id
where v.scope = 'entity' and v.code = 'label' and v.value like '%test%'
```

## Implementation

### ConfigId
Allows to identify each configurable object. The entity id is represented by EntityConfigId class. The field id is represented by FieldConfigId class.

### Config
The aim of this class is to store configuration data for each configurable object.

### ConfigProvider
The configuration provider can be used to manage configuration data inside particular configuration scope. Each configuration provider is a service named **oro_entity_config.provider.{scope}**, where **{scope}** is the name of the configuration scope a provider works with.
For example the following code gets the configuration provider for 'extend' scope.
``` php
<?php

/** @var ConfigProvider $configProvider */
$configProvider = $this->get('oro_entity_config.provider.extend');
```

### ConfigManager
This class is the central access point to entity configuration functionality. It allows to load/save configuration data from/into a database, manage configuration data, manage configuration data cache, retrieve the configuration provider for particular scope, and other.

### Events
 - Events::CREATE_ENTITY       - This event occurs when a new configurable entity is found and its configuration attributes are loaded, but before they are stored in a database.
 - Events::UPDATE_ENTITY       - This event occurs when default values of configuration attributes of existing entity are merged with existing configuration data, but before they are stored in a database.
 - Events::CREATE_FIELD        - This event occurs when a new configurable field is found and its configuration attributes are loaded, but before they are stored in a database.
 - Events::UPDATE_FIELD        - This event occurs when default values of configuration attributes of existing field are merged with existing configuration data, but before they are stored in a database.
 - Events::RENAME_FIELD        - This event occurs when the name of existing field is being changed.
 - Events::PRE_FLUSH           - This event occurs before changes of configuration data is flushed into a database.
 - Events::POST_FLUSH          - This event occurs after all changes of configuration data is flushed into a database.

## Update configuration data

The following command can be used to update configurable entities:
```bash
php bin/console oro:entity-config:update
```
Usually you need to execute this command only in 'dev' mode when new new configuration attribute or whole configuration scope is added.

## Clearing up the cache

The following command removes all data related to configurable entities from the application cache:
```bash
php bin/console oro:entity-config:cache:clear --no-warmup
```

## Debugging configuration data

You can use `oro:entity-config:debug` command to get a different kind of configuration data as well as add/remove/update configuration of entities. To see all available options run this command with `--help` option. As an example the following command shows all configuration data for User entity:
```bash
php bin/console oro:entity-config:debug "Oro\Bundle\UserBundle\Entity\User"
```

## Special case

- [The bundle gives possibility to assign functionality for entity to create and manipulate attributes](Resources/doc/attributes.md)
