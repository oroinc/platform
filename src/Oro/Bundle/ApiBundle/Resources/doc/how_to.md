How to
======


Table of Contents
-----------------
 - [Turn on API for entity](#overview)
 - [Turn on API for entity disabled in "Resources/config/oro/entity.yml"](#turn-on-api-for-entity-disabled-in-resourcesconfigoroentityyml)
 - [Change ACL resource for action](#change-acl-resource-for-action)
 - [Disable access checks for action](#disable-access-checks-for-action)
 - [Disable entity action](#disable-entity-action)
 - [Change delete handler for entity](#change-delete-handler-for-entity)
 - [Change the maximum number of entities that can be deleted by one request](#change-the-maximum-number-of-entities-that-can-be-deleted-by-one-request)


Turn on API for entity
----------------------

By default, API for entities is disabled. To turn on API for some entity, you should add this entity to `Resources/config/oro/api.yml` of your bundle:


```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product: ~
```

Turn on API for entity disabled in Resources/config/oro/entity.yml
------------------------------------------------------------------

The `exclusions` section of `Resources/config/oro/entity.yml` configuration file is used to make an entity or a field not accessible for a user. Also such entities and fields are not accessible via Data API as well. But it is possible that by some reasons you want to override such rules for Data API. It can be done using `exclude` option in `Resources/config/oro/api.yml`.

Let's imagine that you have the following `Resources/config/oro/entity.yml`:

```yaml
oro_entity:
    exclusions:
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity1 }
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity2, field: field1 }
```

To override these rules in Data API you can use the following `Resources/config/oro/api.yml`:

```yaml
oro_api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity1:
            exclude: false # override exclude rule from entity.yml
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity2:
            fields:
                field1:
                    exclude: false # override exclude rule from entity.yml
```

Change ACL resource for action
------------------------------

By default, the following permissions are used to restrict access to an entity in a scope of the specific action:

| Action | Permission |
| --- | --- |
| get | VIEW |
| get_list | VIEW |
| delete | DELETE |
| delete_list | DELETE |

In case if you want to change permission or disable access checks for some action, you can use the `acl_resource` option of `actions` configuration section.

For example, lets's change permissions for `delete` action. You can do at `Resources/config/oro/api.yml` of your bundle: 


```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete:
                    acl_resource: access_entity_view
```

If there is `access_entity_view` ACL resource:

```yaml   
access_entity_view:
    type: entity
    class: Acme\Bundle\ProductBundle\Product
    permission: VIEW
```

As result, the `VIEW` permission will be used instead of `DELETE` permission.


Disable access checks for action
--------------------------------
 
You can disable access checks for some action by setting `null` as a value to `acl_resource` option in `Resources/config/acl.yml`:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                get_list:
                    acl_resource: ~
```
 
Disable entity action
----------------------

When you add an entity to the API, all the actions will be available by default.

In case if an action should not be accessible, you can disable it in `Resources/config/acl.yml`:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete:
                    excluded: true
```

Also, you can use short syntax:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete: false
```

Change delete handler for entity
--------------------------------

By default, entity deletion is processed by [DeleteHandler](../../../SoapBundle/Handler/DeleteHandler.php).

If your want to use another delete handler, you can set it by the `delete_handler` option in `Resources/config/acl.yml`:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            delete_handler: acme.demo.product_delete_handler
```

Please note, that the value of `delete_handler` option is the service id.

Also, you can create own delete handler. The handler class must be derived from [DeleteHandler](../../../SoapBundle/Handler/DeleteHandler.php).

Change the maximum number of entities that can be deleted by one request
------------------------------------------------------------------------

By default, the [delete_list](./actions.md#delete_list-action) action can delete not more than 100 entities. This limit is set by the [SetDeleteLimit](../../Processor/DeleteList/SetDeleteLimit.php) processor.

If your want to use another limit, you can set it by the `max_results` option in `Resources/config/acl.yml`:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete_list:
                    max_results: 200
```

Also you can remove the limit at all. To do this, set `-1` as a value for the `max_results` option:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete_list:
                    max_results: -1
```
