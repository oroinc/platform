Configurable Permissions
========================

Table of Contents
-----------------
 - [Model](#model)
 - [Configuration](#configuration)
 - [Configuration Merging](#configuration-merging)
 - [Configuration Load](#configuration-load)

User can manage visibility of role permissions on View and Edit Role pages.

Model
-----

* **ConfigurablePermission** - model that contains all data of Configurable Permission. It has 3 public methods for
checking if permission or capability is configurable for this ConfigurablePermission:
- isEntityPermissionConfigurable($entityClass, $permission) - check that permission $permission is configurable for
entity class $entityClass; 
- isWorkflowPermissionConfigurable($identity, $permission) - check that permission $permission is configurable for
workflow part with identity $identity;
- isCapabilityConfigurable($capability) - check that capability is configurable

Configuration
-------------

All Configurable Permissions are described in configuration file ``configurable_permissions.yml`` corresponded bundle. 
It has 4 main options:
    - default (bool, by default = false) - is all permissions for the Configurable Name configurable by default;
    - entities (array|bool) - the list of entity classes with permissions. If value is boolean - it will be apply to all 
    permissions for this entity class;
    - capabilities (array) - the list of capabilities;
    - workflows (array|bool) - the list of workflows permissions identities with permissions.If value is boolean - it 
    will be apply to all permissions for this identity.

Example of simple configurable permission configuration.

```
oro_configurable_permissions:
    some_name:                                                      # configurable permission name, will be used by filter
        default: true                                               # is all permissions for this `some_name` configurable by default 
        entities:                                                 
            Oro\Bundle\CalendarBundle\Entity\Calendar:              # entity class
                CREATE: false                                       # hide permission `CREATE` for entity Calendar
                EDIT: true                                          # show permission `EDIT` for entity Calendar
        capabilities:
            oro_acme_some_capability: false                         # hide capability `oro_acme_some_capability` for `some_name`
        workflows:
            workflow1:
                PERFORM_TRANSIT: false                              # hide permission `PERFORM_TRANSIT` for workflow `workflow1`
```

Configuration Merging
=====================

All configurations merge in the boot bundles order. Application collects configurations of all configurable permissions 
with the same name and merge it to one configuration.
Merging uses simple rules:
 * if node value is scalar - value will be replaced
 * if node value is array - this array will be complemented by values from the second configuration

After this step application knows about all permissions and have only one configuration for each permission.

Configuration Load
------------------

To load or update configurable permissions configuration to cache execute a command:

```
security:configurable-permission:load
```
