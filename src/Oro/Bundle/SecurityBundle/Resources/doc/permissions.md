Permissions
===========

Table of Contents
-----------------
 - [Entities](#entities)
 - [Configuration](#configuration)
 - [Configuration Merging](#configuration-merging)
 - [Configuration Load](#configuration-load)

User can define custom Permissions and apply it to any manageable Entity.

Entities
--------

Permission consists of 2 related entities.

* **Permission** - main entity that contains information about specific permission. It contains the most important
information like Permission name, label, groups, the list of PermissionEntities for what Permission can be
applied and the list of PermissionEntities that can't use this Permission.

* **PermissionEntity** - stored entity class names for using with Permission entity.

Configuration
-------------

All custom Permissions are described in configuration file ``permissions.yml`` corresponded bundle. For now there is not
possible to add Permission globally - for all groups (applications) - needed groups should be listed manually. So for
every application needed permissions should be added\updated by creating corresponded ``permissions.yml``.

Look at the example of simple permission configuration.

```
permissions:
    PERMISSION1:                                                    # permission name (should start with a letter, digit or underscore and only contain
                                                                    # letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":")
        label: Label for Permission 1                               # permission label
        description: Permission 1 description                       # (optional) permission description
        apply_to_all: false                                         # (by default = true) is permission apply to all entities by default
        apply_to_entities:                                          # (optional) the list of entities to apply permission
            - 'AcmeDemoBundle:MyEntity1'                            # entity class
            - 'Acme\Bundle\DemoBundle\Entity\MyEntity2'
        group_names:                                                # (by default = ['default]) the list of Groups
            - default                                               # group name
            - frontend

    PERMISSION2:
        label: Label for Permission 2
        description: Permission 2 description
        exclude_entities:                                           # (optional) the list of entities to not apply permission
            - 'AcmeDemoBundle:MyEntity3'
            - 'Acme\Bundle\DemoBundle\Entity\MyEntity4'
```

This configuration describes 2 Permissions:
    1) Permission PERMISSION1 will apply only to entities `MyEntity1` and `MyEntity2`. Allowed groups for that permission are `default` and `frontend`.
    2) Permission PERMISSION2 will apply for all manageable entities except only `MyEntity2` and `MyEntity3`. Allowed groups for that permission is only `default`.

Configuration Merging
=====================

All configurations merge in the boot bundles order. Application collects configurations of all permissions with the same
name and merge their to one configuration.
Merging uses simple rules:
 * if node value is scalar - value will be replaced
 * if node value is array - this array will be complemented by values from the second configuration

After this step application knows about all permissions and have only one configuration for each permission.

Configuration Load
------------------

To load permissions configuration to DB execute a command:

```
security:permission:configuration:load [--permissions [PERMISSIONS]]
```

Optional option `--permissions` allows to load only listed Permissions.
