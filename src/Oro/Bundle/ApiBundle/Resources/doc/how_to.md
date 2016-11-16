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

Please note that an entity, in this example *Oro\Bundle\ReminderBundle\Entity\Reminder*, should have `setInterval` method. This method is called by **create** and **update** actions to set the nested object. 

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

Under term `Extended Many-To-One Associations` we mean resources like `Attachment`, `Comment` or `Note`. Each element of such resource can be assigned to another resource like `User`, `Account`, `Contact`, etc. The relation itself between them is represented by [Many-To-One Unidirectional association](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-one-unidirectional). 

For more detail about such association, please refer to [Configure many-to-one associations](../../EntityExtendBundle/Resources/doc/associations.md#configure-many-to-one-associations)

So, depending on current entity configuration state, each association resource (e.g. attachment) can be assigned to one of the couple of resources (e.g. user, account, contact) that supports such associations. And by calling the same method `getTarget` from the attachment side (source) it will return target entity like `user` or `account` or `contact` (depending to which the particular attachment is attached to).

By default, the API engine will not automatically enable possibility to retrieve targets of such associations.
But this behaviour can be achieved via configuration in `api.yml` files, for instance:

```yaml
api:
    entities:
        Oro\Bundle\AttachmentBundle\Entity\Attachment:
            fields:
                target:
                    data_type: association:manyToOne
```

```yaml
api:
    entities:
        Oro\Bundle\NoteBundle\Entity\Note:
            fields:
                target:
                    data_type: association:manyToOne
```

After applying configuration like above, the `target` relationship will be available in scope of `get_list`, `get`, `create`, `update`, etc. actions. 
Also it will be possible to perform actions `get_relationship`, `get_subresource`, `update_relationship`.

For example:

- `get_relationship` target for `attachment` with id `1` - GET /api/attachments/1/relationships/target

```json
{
  "data": {
    "type": "users",
    "id": "1"
  }
}
```

- `get_relationship` target for `attachment` with id `2` - GET /api/attachments/2/relationships/target

```json
{
  "data": {
    "type": "accounts",
    "id": "14"
  }
}
```

- `update_relationship` target for `attachment` with id `1` - PATCH /api/attachments/1/relationships/target

and Request body, e.g.

```json
{
  "data": {
    "type": "users",
    "id": "11"
  }
}
```

will assign the `attachment` with id `1` to the `user` with id `11`


- `get_subresource` target for `attachment` with id `1` - GET /api/attachments/1/target

```json
{
  "data": {
    "type": "users",
    "id": "1",
    "attributes": {
      "username": "admin",
      "email": "admin@local.com",
      "createdAt": "2016-10-28T12:31:01Z",
      "updatedAt": "2016-10-28T14:43:33Z",
      ...
    },
    "relationships": {
      ...
    }
  }
}
```

Sometimes it is required to have ability to manipulate an inverse part of extend associations. For example, to see all the notes that was assigned for some entity.

Developer can do this with declaration an API processor service based on [InverseAssociationRelationFields](../../ApiBundle/Processor/Config/SharedInverseAssociationRelationFields.php) class.

For example:

```yaml
# your_bundle/Resources/config/services.yml

you_bundle.api.add_inverse_association:
    class: Oro\Bundle\ApiBundle\Processor\Config\Shared\InverseAssociationRelationFields
    arguments:
        - '@oro_entity_config.provider.extend'
        - '@oro_api.doctrine_helper'
        - '@oro_api.value_normalizer'
    calls:
        - [setAssociationClass, ['Your\Bundle\Entity\AssociationSourceEntity']]
    tags:
        - { name: oro.api.processor, action: get_config, extra: !identifier_fields_only&definition, priority: -29
```

During declaration, the developer should set the source class name as parameter to `setAssociationClass` method call.

After that, all the target entities will have additional API resources to get relationships and subresources and an API resource to add new relationship.
