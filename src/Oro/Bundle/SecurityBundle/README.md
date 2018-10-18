# OroSecurityBundle

OroSecurityBundle extends Symfony security capabilities to enable a role-based ACL security system in the Oro applications.

The bundle enables developers to set up access restrictions for entities and non-entity related actions using the DOC-block annotations and the YAML configuration files. It also provides UI for application administrators to configure the entity-specific permissions for particular user roles based on the entity ownership.

## ACL

At first it is important to say that all benefits of Symfony ACL based security is supported by Oro as well. It means that access can be granted/denied on the following scopes:

 - **Class-Scope**: Allows to set permissions for all objects with the same type.
 - **Object-Scope**: Allows to set permissions for one specific object.
 - **Class-Field-Scope**: Allows to set permissions for all objects with the same type, but only to a specific field of the objects.
 - **Object-Field-Scope**: Allows to set permissions for a specific object, and only to a specific field of that object.

Detailed information about Symfony ACL based security model you can read in the Symfony documentation:

 - http://symfony.com/doc/current/cookbook/security/acl.html
 - http://symfony.com/doc/current/cookbook/security/acl_advanced.html

In additional Oro allows you to protect data on different levels:

 - **System**: Allows to gives a user a permissions to access to all records within the system.
 - **Organization**: Allows to gives a user a permissions to access to all records within the organization, regardless of the business unit hierarchical level to which a record belongs or the user is assigned to.
 - **Division**: Allows to gives a user a permissions to access to records in all business units are assigned to the user and all business units subordinate to business units are assigned to the user.
 - **Business Unit**: Allows to gives a user a permissions to access to records in all business units are assigned to the user.
 - **User**: Allows to gives a user a permissions to access to own records and records that are shared with the user.

Also the following permissions are supported for entities:

 - **VIEW**: Controls whether a user is allowed to view a record.
 - **CREATE**: Controls whether a user is allowed to create a record.
 - **EDIT**: Controls whether a user is allowed to modify a record.
 - **DELETE**: Controls whether a user is allowed to delete a record.
 - **ASSIGN**: Controls whether a user is allowed to change an owner of a record. For example assign a record to another user.
 - **SHARE**: Controls whether the user can share a record with another user.

 `*` **NOTE: SHARE functionality is implemented in Enterprise Edition**

And these permissions are supported for fields:
 
 - **VIEW**: Controls whether a user is allowed to view a field.
 - **EDIT**: Controls whether a user is allowed to modify a field.

## Features

You can find additional information about the bundle's features in their dedicated sections:
- [Implementation](./Resources/doc/implementation.md)
- [UI](./Resources/doc/ui.md)
- [ACL manager](./Resources/doc/acl-manager.md)
- [Access levels](./Resources/doc/access-levels.md)
- [Field ACL](./Resources/doc/field-acl.md)
- [Custom listeners](./Resources/doc/custom-listeners.md)
- [Examples](./Resources/doc/examples.md)
- [Access rules](./Resources/doc/access-rules.md)

## Permissions

The `OroSecurityBundle` provides possibility to use custom permissions for entities.
See [Permissions](./Resources/doc/permissions.md) for details.

## Configurable Permissions

See [Configurable Permissions](./Resources/doc/configurable-permissions.md) for details.
