# How To

 - [Turn on API for an Entity](#overview)
 - [Turn on API for an Entity Disabled in "Resources/config/oro/entity.yml"](#turn-on-api-for-an-entity-disabled-in-resourcesconfigoroentityyml)
 - [Change an ACL Resource for an Action](#change-an-acl-resource-for-an-action)
 - [Disable Access Checks for an Action](#disable-access-checks-for-an-action)
 - [Disable an Entity Action](#disable-an-entity-action)
 - [Change the Delete Handler for an Entity](#change-the-delete-handler-for-an-entity)
 - [Change the Maximum Number of Entities that Can Be Deleted by One Request](#change-the-maximum-number-of-entities-that-can-be-deleted-by-one-request)
 - [Configure a Nested Object](#configure-a-nested-object)
 - [Configure a Nested Association](#configure-a-nested-association)
 - [Configure an Extended Many-To-One Association](#configure-an-extended-many-to-one-association)
 - [Configure an Extended Many-To-Many Association](#configure-an-extended-many-to-many-association)
 - [Configure an Extended Multiple Many-To-One Association](#configure-an-extended-multiple-many-to-one-association)
 - [Add a Custom Controller](#add-a-custom-controller)
 - [Add a Custom Route](#add-a-custom-route)
 - [Using a Non-primary Key to Identify an Entity](#using-a-non-primary-key-to-identify-an-entity)


## Turn on API for an Entity

By default, API for entities is disabled. To turn on API for an entity, add the entity to `Resources/config/oro/api.yml` of your bundle:


```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product: ~
```

## Turn on API for an Entity Disabled in "Resources/config/oro/entity.yml"

The `exclusions` section of the `Resources/config/oro/entity.yml` configuration file is used to make an entity or a field inaccessible for a user. The entities and fields from this section are inaccessible via the data API as well. However, it is possible to override this rule for the data API. To do this, use the `exclude` option in `Resources/config/oro/api.yml`.

Let us consider the case when you have the following `Resources/config/oro/entity.yml`:

```yaml
oro_entity:
    exclusions:
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity1 }
        - { entity: Acme\Bundle\AcmeBundle\Entity\AcmeEntity2, field: field1 }
```

To override these rules in the data API, add the following lines to the `Resources/config/oro/api.yml`:

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

## Change an ACL Resource for an Action

By default, the following permissions are used to restrict access to an entity in a scope of the specific action:

| Action | Permission |
| --- | --- |
| [get](./actions.md#get-action) | VIEW |
| [get_list](./actions.md#get_list-action) | VIEW |
| [delete](./actions.md#delete-action) | DELETE |
| [delete_list](./actions.md#delete_list-action) | DELETE |
| [create](./actions.md#create-action) | CREATE and VIEW |
| [update](./actions.md#update-action) | EDIT and VIEW |
| [get_subresource](./actions.md#get_subresource-action) | VIEW |
| [get_relationship](./actions.md#get_relationship-action) | VIEW |
| [update_relationship](./actions.md#update_relationship-action) | EDIT and VIEW |
| [add_relationship](./actions.md#add_relationship-action) | EDIT and VIEW |
| [delete_relationship](./actions.md#delete_relationship-action) | EDIT and VIEW |

If you want to change permission or disable access checks for some action, you can use the `acl_resource` option of the `actions` configuration section.

For example, to change permissions for the `delete` action, add the following lines to the `Resources/config/oro/api.yml` of your bundle: 


```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete:
                    acl_resource: access_entity_view
```

If there is the `access_entity_view` ACL resource:

```yaml   
access_entity_view:
    type: entity
    class: Acme\Bundle\ProductBundle\Product
    permission: VIEW
```

As a result, the `VIEW` permission will be used instead of the `DELETE` permission.


## Disable Access Checks for an Action
 
You can disable access checks for some action by setting `null` as a value for the `acl_resource` option in `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                get_list:
                    acl_resource: ~
```
 
## Disable an Entity Action

When you add an entity to the API, all the actions will be available by default.

If an action should be inaccessible, disable it in `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete:
                    exclude: true
```

You can use the short syntax:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete: false
```

## Change the Delete Handler for an Entity

By default, entity deletion is processed by [DeleteHandler](../../../SoapBundle/Handler/DeleteHandler.php).

If your want to use another delete handler, set it using the `delete_handler` option in `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            delete_handler: acme.demo.product_delete_handler
```

Please note that the value of the `delete_handler` option is a service id.

Additionally, you can create a custom delete handler. The handler class must be derived from [DeleteHandler](../../../SoapBundle/Handler/DeleteHandler.php).

## Change the Maximum Number of Entities that Can Be Deleted by One Request

By default, the [delete_list](./actions.md#delete_list-action) action can delete not more than 100 entities. This limit is set by the [SetDeleteLimit](../../Processor/DeleteList/SetDeleteLimit.php) processor.

If your want to use another limit, set it using the `max_results` option in `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete_list:
                    max_results: 200
```

You can remove the limit at all. To do this, set `-1` as a value for the `max_results` option:

```yaml
api:
    entities:
        Acme\Bundle\ProductBundle\Product:
            actions:
                delete_list:
                    max_results: -1
```

## Configure a Nested Object

Sometimes it is required to group several fields and expose them as a nested object in the data API. For example, consider the case when an entity has two fields `intervalNumber` and `intervalUnit` but you need to expose them in API as `number` and `unit` properties of the `interval` field. To achieve it, use the following configuration:

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

Please note that an entity, in this example *Oro\Bundle\ReminderBundle\Entity\Reminder*, should have the `setInterval` method. This method is called by the [create](./actions.md#create-action) and [update](./actions.md#update-action) actions to set the nested object. 

Here is an example of how the nested objects look in JSON.API:

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

## Configure a Nested Association

Sometimes a relationship with a group of entities is implemented as two fields, "entityClass" and "entityId", rather than [many-to-one extended association](../../../EntityExtendBundle/Resources/doc/associations.md). But in the data API these fields should be represented as a regular relationship. To achieve this, a special data type named `nestedAssociation` was implemented. For example, let us suppose that an entity has two fields `sourceEntityClass` and `sourceEntityId` and you need to expose them in API as the `source` relationship. To achieve this, use the following configuration:

```yaml
api:
    entities:
        Oro\Bundle\OrderBundle\Entity\Order:
            fields:
                source:
                    data_type: nestedAssociation
                    fields:
                        __class__:
                            property_path: sourceEntityClass
                        id:
                            property_path: sourceEntityId
                sourceEntityClass:
                    exclude: true
                sourceEntityId:
                    exclude: true
```

Here is an example of how the nested association looks in JSON.API:

```json
{
  "data": {
    "type": "orders",
    "id": "1",
    "relationships": {
      "source": {
        "type": "contacts",
        "id": 123
      }
    }
  }
}
```

## Configure an Extended Many-To-One Association

For information about extended associations, see the [Associations](../../../EntityExtendBundle/Resources/doc/associations.md) topic.

Depending on the current entity configuration, each association resource (e.g. attachment) can be assigned to one of the resources (e.g. user, account, contact) that support such associations.

By default, there is no possibility to retrieve targets of such associations. To make targets available for retrieving, enable this in `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Oro\Bundle\AttachmentBundle\Entity\Attachment:
            fields:
                target:
                    data_type: association:manyToOne
```

After applying the configuration, the `target` relationship becomes available for the [get_list](./actions.md#get_list-action), [get](./actions.md#get-action), [create](./actions.md#create-action), and [update](./actions.md#update-action) actions. The `target` relationship becomes also available as a subresource and thus, it is possible to perform the [get_subresource](./actions.md#get_subresource-action), [get_relationship](./actions.md#get_relationship-action) and [update_relationship](./actions.md#update_relationship-action) actions.

The `data_type` parameter has format: `association:relationType:associationKind`, where:

 - `relationType` part should have the "manyToOne: value for the extended Many-To-One association;
 - `associationKind` is the optional part that represents the kind of the association.

## Configure an Extended Many-To-Many Association

For information about extended associations, see the [Associations](../../../EntityExtendBundle/Resources/doc/associations.md) topic.

Depending on the current entity configuration, each association resource (e.g. call) can be assigned to several resources (e.g. user, account, contact) that support such associations.

By default, there is no possibility to retrieve targets of such associations. To make targets available for retrieving, enable this in `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Oro\Bundle\CallBundle\Entity\Call:
            fields:
                activityTargets:
                    data_type: association:manyToMany:activity
```

After applying the configuration, the `activityTargets` relationship becomes available in scope of the following actions:

- [get_list](./actions.md#get_list-action) 
- [get](./actions.md#get-action) 
- [create](./actions.md#create-action)
- [update](./actions.md#update-action)

The `activityTargets` relationship also becomes  available as a subresource and thus, it is possible to perform the following actions:
 
- [get_subresource](./actions.md#get_subresource-action) 
- [get_relationship](./actions.md#get_relationship-action) 
- [add_relationship](./actions.md#add_relationship-action)
- [update_relationship](./actions.md#update_relationship-action)
- [delete_relationship](./actions.md#delete_relationship-action)

The `data_type` parameter has format: `association:relationType:associationKind`, where:

 - `relationType` part should have the 'manyToMany' value for the extended Many-To-Many association;
 - `associationKind` is the optional part that represents the kind of the association.

## Configure an Extended Multiple Many-To-One Association

For information about extended associations, see the [Associations](../../../EntityExtendBundle/Resources/doc/associations.md) topic.

Depending on the current entity configuration, each association resource (e.g. call) can be assigned to several resources (e.g. user, account, contact) that support such associations. However, in case of multiple many-to-one association, a resource can be associated with only one other resource of each type. For example, a call can be associated only with one user, one account, etc.

By default, there is no possibility to retrieve targets of such associations. To make targets available for retrieving, enable this in `Resources/config/oro/api.yml`:

```yaml
api:
    entities:
        Oro\Bundle\CallBundle\Entity\Call:
            fields:
                targets:
                    data_type: association:multipleManyToOne
```

After applying the configuration, the `targets` relationship becomes available in scope of the following actions:
 
- [get_list](./actions.md#get_list-action)
- [get](./actions.md#get-action)
- [create](./actions.md#create-action)
- [update](./actions.md#update-action)

The `targets` relationship also becomes  available as a subresource and thus, it is possible to perform the following actions:

- [get_subresource](./actions.md#get_subresource-action)
- [get_relationship](./actions.md#get_relationship-action)
- [add_relationship](./actions.md#add_relationship-action)
- [update_relationship](./actions.md#update_relationship-action)
- [delete_relationship](./actions.md#delete_relationship-action)

The `data_type` parameter has format: `association:relationType:associationKind`, where:

 - `relationType` part should have the 'multipleManyToOne' value for the extended Multiple Many-To-One association;
 - `associationKind` is the optional part that represents the kind of the association.

## Add a Custom Controller

By default, all REST API resources are handled by [RestApiController](../../Controller/RestApiController.php) that handles the following actions:

 - [get_list](./actions.md#get_list-action)
 - [get](./actions.md#get-action)
 - [delete](./actions.md#delete-action)
 - [delete_list](./actions.md#delete_list-action)
 - [create](./actions.md#create-action)
 - [update](./actions.md#update-action)
 - [get_subresource](./actions.md#get_subresource-action)
 - [get_relationship](./actions.md#get_relationship-action)
 - [update_relationship](./actions.md#update_relationship-action)
 - [add_relationship](./actions.md#add_relationship-action)
 - [delete_relationship](./actions.md#delete_relationship-action)

If this controller cannot handle the implementation of your REST API resources, you can register a custom controller. Please note that this is not recommended and should be used only in very special cases. Having a custom controller implies that a lot of logic is to be implemented from scratch, including:

 - extracting and validation of the input data
 - building and formatting the output document
 - error handling
 - loading data from the database
 - saving data to the database
 - implementing relationships with other API resources
 - documenting such API resources

If you know about these disadvantages and still want to proceed, to register a custom controller, perform the following steps:

 1. Create a controller.
 2. Register the created controller using the `Resources/oro/routing.yml` configuration file.

Here is an example of the controller:

```php
<?php

namespace Acme\Bundle\AppBundle\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class MyResourceController extends Controller
{
    /**
     * Retrieve a specific record.
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     resource=true,
     *     description="Get a resource",
     *     views={"rest_json_api"},
     *     section="myresources",
     *     requirements={
     *          {
     *              "name"="id",
     *              "dataType"="integer",
     *              "requirement"="\d+",
     *              "description"="The 'id' requirement description."
     *          }
     *     },
     *     filters={
     *          {
     *              "name"="aFilter",
     *              "dataType"="string",
     *              "requirement"=".+",
     *              "description"="The 'aFilter' filter description."
     *          }
     *     },
     *     output={
     *          "class"="Your\Namespace\Class",
     *          "fields"={
     *              {
     *                  "name"="aField",
     *                  "dataType"="string",
     *                  "description"="The 'aField' field description."
     *              }
     *          }
     *     },
     *     statusCodes={
     *          200="Returned when successful",
     *          500="Returned when an unexpected error occurs"
     *     }
     * )
     *
     * @return Response
     */
    public function getAction(Request $request)
    {
        // @todo: add an implementaution here
    }
}
```

An example of the `Resources/oro/routing.yml` configuration file:

```yaml
acme_api_get_my_resource:
    path: /api/myresources/{id}
    methods: [GET]
    defaults:
        _controller: AcmeAppBundle:Api\MyResource:get
    options:
        group: rest_api
```

For information about the `ApiDoc` annotation, see [Symfony documentation](https://symfony.com/doc/current/bundles/NelmioApiDocBundle/the-apidoc-annotation.html). 
To learn about all possible properties of the `fields` option, see [AbstractFormatter class in NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle/blob/2.x/Formatter/AbstractFormatter.php). Please note that the `fields` option can be used inside the `input` and `output` options.

Use the [oro:api:doc:cache:clear](./commands.md#oroapidoccacheclear) command to apply changes in the `ApiDoc` annotation to [API Sandbox](https://www.oroinc.com/doc/orocrm/current/book/data-api#api-sandbox).

## Add a Custom Route

As desctibed in [Add a Custom Controller](#add-a-custom-controller), [RestApiController](../../Controller/RestApiController.php) handles all registered REST API resources, and in the most cases you do not need to change this.
But sometimes you need to change default mapping between URI and an action of this controller for some
REST API resources.
For example, imagine REST API resource for a profile of the logged in user. Let's imagine that URI of this
resource should be `/api/userprofile`. If you take a look at [routing.yml](../../Resources/config/oro/routing.yml)
you will see that this URI is matched by `/api/{entity}` pattern, but the action that handles this
pattern works with a list of entities, not with a single entity. The challenge is to map `/api/userprofile` to
`OroApiBundle:RestApi:item` action that works with a single entity and to remove handling of
`/api/userprofile/{id}`. This can be achieved using own route definition with `override_path` option.

Here is an example of the `Resources/oro/routing.yml` configuration file:

```yaml
acme_rest_api_user_profile:
    path: /api/userprofile
    defaults:
        _controller: OroApiBundle:RestApi:item
        entity: userprofile
    options:
        group: rest_api
        override_path: /api/userprofile/{id}
```

## Using a Non-primary Key to Identify an Entity

By default, a primary key is used to identify ORM entities in API. If you need another field as an identifier, specify it using the `identifier_field_names` option.

For example, let your entity has the `id` field that is the primary key and the `uuid` field that contains a unique value for each entity. To use the `uuid` field to identify the entity, add the following in `Resources/config/oro/api.yml`:

```yml
api:
    entities:
        Acme\Bundle\AppBundle\Entity\SomeEntity:
            identifier_field_names: ['uuid']
```

You can also exclude the `id` field (primary key) if you do not want to expose it via API:

```yml
api:
    entities:
        Acme\Bundle\AppBundle\Entity\SomeEntity:
            identifier_field_names: ['uuid']
            fields:
                id:
                    exclude: true
```
