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
 - [Configure nested object](#configure-nested-object)
 - [Turn on Extended Many-To-One Associations](#turn-on-extended-many-to-one-associations)


Turn on API for entity
----------------------

By default, API for entities is disabled. To turn on API for some entity, you should add this entity to `Resources/config/oro/api.yml` of your bundle:


```yaml
api:
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
api:
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
api:
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
 
You can disable access checks for some action by setting `null` as a value to `acl_resource` option in `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                get_list:
                    acl_resource: ~
```
 
Disable entity action
----------------------

When you add an entity to the API, all the actions will be available by default.

In case if an action should not be accessible, you can disable it in `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete:
                    excluded: true
```

Also, you can use short syntax:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete: false
```

Change delete handler for entity
--------------------------------

By default, entity deletion is processed by [DeleteHandler](../../../SoapBundle/Handler/DeleteHandler.php).

If your want to use another delete handler, you can set it by the `delete_handler` option in `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            delete_handler: acme.demo.product_delete_handler
```

Please note, that the value of `delete_handler` option is the service id.

Also, you can create own delete handler. The handler class must be derived from [DeleteHandler](../../../SoapBundle/Handler/DeleteHandler.php).

Change the maximum number of entities that can be deleted by one request
------------------------------------------------------------------------

By default, the [delete_list](./actions.md#delete_list-action) action can delete not more than 100 entities. This limit is set by the [SetDeleteLimit](../../Processor/DeleteList/SetDeleteLimit.php) processor.

If your want to use another limit, you can set it by the `max_results` option in `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete_list:
                    max_results: 200
```

Also you can remove the limit at all. To do this, set `-1` as a value for the `max_results` option:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete_list:
                    max_results: -1
```

Configure nested object
-----------------------

Sometimes it is required to group several fields and expose them as an nested object in Data API. For example lets suppose that an entity has two fields `intervalNumber` and `intervalUnit` but you need to expose them in API as `number` and `unit` properties of `interval` field. This can be achieved by the following configuration:

```yaml
api:
    entities:
        Oro\Bundle\ReminderBundle\Entity\Reminder:
            fields:
                interval:
                    data_type: nestedObject
                    form_options:
                        data_class: Oro\Bundle\ReminderBundle\Model\ReminderInterval
                        by_reference: false
                    fields:
                        number:
                            property_path: intervalNumber
                        unit:
                            property_path: intervalUnit
                intervalNumber:
                    exclude: true
                intervalUnit:
                    exclude: true
```

Please note that an entity, in this example *Oro\Bundle\ReminderBundle\Entity\Reminder*, should have `setInterval` method. This method is called by [create](./actions.md#create-action) and [update](./actions.md#update-action) actions to set the nested object. 

Here is an example how the nested objects looks in JSON.API:

```json
{
  "data": {
    "type": "reminders",
    "id": "1",
    "attributes": {
      "interval": {
        "number": 2,
        "unit": "H"
      }
    }
  }
}
```

Turn on Extended Many-To-One Associations
-----------------------------------------

For detail what are extended associations, please refer to [Associations](../../../EntityExtendBundle/Resources/doc/associations.md) topic.

Depending on current entity configuration, each association resource (e.g. attachment) can be assigned to one of the couple of resources (e.g. user, account, contact) that supports such associations.

### Owning side of an association 

By default, there is no possibility to retrieve targets of such associations. But this behaviour can be enabled via configuration in `Resources/config/oro/api.yml`, for instance:

```yaml
api:
    entities:
        Oro\Bundle\AttachmentBundle\Entity\Attachment:
            fields:
                target:
                    data_type: association:manyToOne
```

After applying configuration like above, the `target` relationship will be available in scope of [get_list](./actions.md#get_list-action), [get](./actions.md#get-action), [create](./actions.md#create-action) and [update](./actions.md#update-action) actions. Also the `target` relationship will be available as subresource and it will be possible to perform [get_subresource](./actions.md#get_subresource-action), [get_relationship](./actions.md#get_relationship-action) and [update_relationship](./actions.md#update_relationship-action) actions.

### Inverse side of an association 

Similar to an owning side of association the inverse part should be enabled manually. This can be done by registering [AddInverseToOneAssociation](../../ApiBundle/Processor/Config/Shared/AddInverseToOneAssociation.php) API processor.

For example:

```yaml
# Resources/config/services.yml

acme.api.add_inverse_association:
    class: Oro\Bundle\ApiBundle\Processor\Config\Shared\AddInverseToOneAssociation
    arguments:
        - '@oro_entity_config.provider.extend'
        - '@oro_api.doctrine_helper'
        - '@oro_api.value_normalizer'
    calls:
        - [setAssociationClass, ['Your\Bundle\Entity\AssociationSourceEntity']]
    tags:
        - { name: oro.api.processor, action: get_config, extra: !identifier_fields_only&definition, priority: -29
```

Please note that the class name of the owning side entity should be set via `setAssociationClass` method in the service declaration. Also if an association has a kind it should be set via `setAssociationKind` method.

After that, a relationship to the owning side entity will be available as subresource for all inverse side entities and it will be possible to perform [get_subresource](./actions.md#get_subresource-action), [get_relationship](./actions.md#get_relationship-action) and [add_relationship](./actions.md#add_relationship-action) actions.
