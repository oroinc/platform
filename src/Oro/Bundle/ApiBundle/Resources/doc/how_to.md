# How To

 - [Turn on API for an Entity](#turn-on-api-for-an-entity)
 - [Turn on API for an Entity Disabled in "Resources/config/oro/entity.yml"](#turn-on-api-for-an-entity-disabled-in-resourcesconfigoroentityyml)
 - [Enable Advanced Operators for String Filter](#enable-advanced-operators-for-string-filter)
 - [Enable Case-insensitive String Filter](#enable-case-insensitive-string-filter)
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
 - [Enable API for an Entity Without Identifier](#enable-api-for-an-entity-without-identifier)
 - [Enable Custom API](#enable-custom-api)
 - [Add a Predefined Identifier for API Resource](#add-a-predefined-identifier-for-api-resource)
 - [Add a Computed Field](#add-a-computed-field)
 - [Disable HATEOAS](#disable-hateoas)


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

## Enable Advanced Operators for String Filter

By performance reasons the following operators are disabled out of the box:

- `~` (`contains`) - uses `LIKE %text%` to check that a field value contains the text
- `!~` (`not_contains`) - uses `NOT LIKE %text%` to check that a field value does not contain the text
- `^` (`starts_with`) - uses `LIKE text%` to check that a field value starts with the text
- `!^` (`not_starts_with`) - uses `NOT LIKE text%` to check that a field value does not start with the text
- `$` (`ends_with`) - uses `LIKE %text` to check that a field value ends with the text
- `!$` (`not_ends_with`) - uses `NOT LIKE %text` to check that a field value does not end with the text

To enable these operators use `operators` option for filters in `Resources/config/oro/api.yml`, e.g.:

```yaml
api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity1:
            filters:
                fields:
                    field1:
                        operators: ['=', '!=', '*', '!*', '~', '!~', '^', '!^', '$', '!$']
```

## Enable Case-insensitive String Filter

Depending on the [collation](https://en.wikipedia.org/wiki/Collation) settings of your database the case-insensitive
filtering may be already enforced to be used on the database level. For example, if you are using MySQL database with
`utf8_unicode_ci` collation you do not need to do anything to enable the case-insensitive filtering. But if the
collation of your database or a particular field is not case-insensitive and you need to enable the case-insensitive
filtering for this field, you can use `case_insensitive` option for a filter in `Resources/config/oro/api.yml`, e.g.:

```yaml
api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity1:
            filters:
                fields:
                    field1:
                        options:
                            case_insensitive: true
```

**Please note** that the `LOWER` function will be used in this case and it can impact performance
if there is no [proper index](https://use-the-index-luke.com/sql/where-clause/functions/case-insensitive-search).

Also sometimes data in the database are already converted to lowercase or uppercase, in this case you can use
`value_transformer` option to convert the filter value to before it will be passed to the database query, e.g.:

```yaml
api:
    entities:
        Acme\Bundle\AcmeBundle\Entity\AcmeEntity1:
            filters:
                fields:
                    field1:
                        options:
                            value_transformer: strtoupper # convert the filter value to uppercase
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

Please note that fields used in a nested association, in this example `sourceEntityClass` and `sourceEntityId`, are automatically excluded from the result and you do not need to mark them with `exclude` option. Moreover, they will be excluded even if you mark them with `exclude: false` in a configuration file.

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
 - implementing OPTIONS HTTP method for such API resources

If you know about these disadvantages and still want to proceed, to register a custom controller, perform the following steps:

 1. Create a controller.
 2. Register the created controller using the `Resources/config/oro/routing.yml` configuration file.

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

An example of the `Resources/config/oro/routing.yml` configuration file:

```yaml
acme_api_get_my_resource:
    path: '%oro_api.rest.prefix%myresources/{id}'
    methods: [GET]
    defaults:
        _controller: AcmeAppBundle:Api\MyResource:get
    options:
        group: rest_api
```

For information about the `ApiDoc` annotation, see [Symfony documentation](https://symfony.com/doc/2.x/bundles/NelmioApiDocBundle/the-apidoc-annotation.html). 
To learn about all possible properties of the `fields` option, see [AbstractFormatter class in NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle/blob/2.x/Formatter/AbstractFormatter.php). Please note that the `fields` option can be used inside the `input` and `output` options.

Use the [oro:api:doc:cache:clear](./commands.md#oroapidoccacheclear) command to apply changes in the `ApiDoc` annotation to [API Sandbox](https://oroinc.com/orocrm/doc/current/dev-guide/web-api#api-sandbox).

## Add a Custom Route

As described in [Add a Custom Controller](#add-a-custom-controller), [RestApiController](../../Controller/RestApiController.php) handles all registered REST API resources, and in the most cases you do not need to change this.
But sometimes you need to change default mapping between URI and an action of this controller for some
REST API resources.
For example, imagine REST API resource for a profile of the logged in user. Let's imagine that URI of this
resource should be `/api/userprofile`. If you take a look at [routing.yml](../../Resources/config/oro/routing.yml)
you will see that this URI is matched by `/api/{entity}` pattern, but the action that handles this
pattern works with a list of entities, not with a single entity. The challenge is to map `/api/userprofile` to
`OroApiBundle:RestApi:item` action that works with a single entity and to remove handling of
`/api/userprofile/{id}`. This can be achieved using own route definition with `override_path` option.

Here is an example of the `Resources/config/oro/routing.yml` configuration file:

```yaml
acme_rest_api_user_profile:
    path: '%oro_api.rest.prefix%userprofile'
    defaults:
        _controller: OroApiBundle:RestApi:item
        entity: userprofile
    options:
        group: rest_api
        override_path: '%oro_api.rest.prefix%userprofile/{id}'
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

## Enable API for an Entity Without Identifier

Sometimes it is required to create API resource that does not have an identifier. An example of such API resources
can be resources for registering a new account or logging in a user.

The following steps describes how to create such API resources:

- Create a PHP class that will represent API resource. Usually such classes are named as models and located in
  `Api/Model` directory. For example:

  ```php
  <?php

  namespace Acme\Bundle\AppBundle\Api\Model;

  class Account
  {
      /** @var string|null */
      private $name;

      /**
       * @return string|null
       */
      public function getName()
      {
          return $this->name;
      }

      /**
       * @param string|null $name
       */
      public function setName($name)
      {
          $this->name = $name;
      }
  }
  ```

- Describe the model via `Resources/config/oro/api.yml` configuration file in your bundle, e.g.:

  ```yaml
  api:
      entity_aliases:
          Acme\Bundle\AppBundle\Api\Model\Account:
              alias: registeraccount
              plural_alias: registeraccount
      entities:
          Acme\Bundle\AppBundle\Api\Model\Account:
              fields:
                  name:
                      data_type: string
                      description: The user name
                      form_options:
                          constraints:
                              - NotBlank: ~
              actions:
                  create:
                      description: Register a new account
                  get: false
                  update: false
                  delete: false
  ```

- Register a route via `Resources/config/oro/routing.yml` configuration file in your bundle using
 `OroApiBundle:RestApi:itemWithoutId` as a controller, e.g.:

  ```yml
  acme_rest_api_register_account:
      path: '%oro_api.rest.prefix%registeraccount'
      defaults:
          _controller: OroApiBundle:RestApi:itemWithoutId
          entity: registeraccount
      options:
          group: rest_api
  ```

- Create a processor to handle data, e.g.:

  ```php
  <?php

  namespace Acme\Bundle\AppBundle\Api\Processor;

  use Acme\Bundle\AppBundle\Api\Model\Account;
  use Oro\Component\ChainProcessor\ContextInterface;
  use Oro\Component\ChainProcessor\ProcessorInterface;

  class RegisterAccount implements ProcessorInterface
  {
      /**
       * {@inheritdoc}
       */
      public function process(ContextInterface $context)
      {
          /** @var Account $account */
          $account = $context->getResult();

          // implement registration of a new account
      }
  }
  ```

- Register a processor in the dependency injection container, e.g.:

  ```yaml
  services:
      acme.api.register_account:
          class: Acme\Bundle\AppBundle\Api\Processor\RegisterAccount
          tags:
              - { name: oro.api.processor, action: create, group: save_data, class: Acme\Bundle\AppBundle\Api\Model\Account }
  ```

## Enable Custom API

Before begin please ensure that you are familiar with [API request type](./request_type.md).

Lets imagine you need API that will be used for an integration with some ERP system. In this case,
to simplify the development and to avoid unnecessary API calls, it will be good if your API resources will have
the same identifiers as the ERP system. The easiest way to achieve this is to create `erpId` field for each entity
and map this field as the identifier of API resource via
[identifier_field_names](#using-a-non-primary-key-to-identify-an-entity) configuration option. But the drawback of this
approach is that you have to change existing API, and as the result it may lead to failure of existing API clients.
To avoid this you can keep existing API unchanged and create a new type of API that will have all features
of existing API and will have modifications specific for this new integration as well.

To do this you need to follow several simple steps:

- Decide how to API clients should inform server that they need to work with new type of API. The simplest way is
  to use custom HTTP header. If a client sends this header it will work with new API, if it does not it will work
  with already existing API. Lets assume that we will use `X-Integration-Type` header to switch
  API types. If this header is sent and its value is `ERP` the new API will be used; otherwise, the already
  existing API will be used.
- Decide which name of the request type you will use for the new API. Lets assume it will be `erp`.
- Decide which name of API configuration files you will use to add modifications specific for the new API.
  Lets assume it will be `api_erp.yml`.
- Add the new type of API to ApiBundle and configure API Sandbox via `Resources/config/oro/app.yml` configuration
  file in your bundle:

  ```yaml
  oro_api:
      # add API type for ERP integration
      config_files:
          erp:
              # load API configuration for ERP integration from two types of files, api_erp.yml and api.yml
              # the first file has higher priority and any configuration in this file will override
              # configuration from the second one
              file_name: [api_erp.yml, api.yml]
              # use this configuration only if ERP integration API is requested
              request_type: ['erp']

      # configure API Sandbox
      api_doc_views:
          erp_rest_json_api:
              label: ERP Integration
              headers:
                  Content-Type: application/vnd.api+json
                  X-Integration-Type: ERP
              request_type: ['rest', 'json_api', 'erp']
  ```

- Create a processor that will check the request header and add `erp` request type to the execution context
  of processors:

   ```php
   <?php

   namespace Acme\Bundle\AppBundle\Api\Processor;

   use Oro\Component\ChainProcessor\ContextInterface;
   use Oro\Component\ChainProcessor\ProcessorInterface;
   use Oro\Bundle\ApiBundle\Processor\Context;

   class CheckErpRequestType implements ProcessorInterface
   {
       const REQUEST_HEADER_NAME = 'X-Integration-Type';
       const REQUEST_HEADER_VALUE = 'ERP';
       const REQUEST_TYPE = 'erp';

       /**
        * {@inheritdoc}
        */
       public function process(ContextInterface $context)
       {
           /** @var Context $context */

           $requestType = $context->getRequestType();
           if (!$requestType->contains(self::REQUEST_TYPE)
               && self::REQUEST_HEADER_VALUE === $context->getRequestHeaders()->get(self::REQUEST_HEADER_NAME)
           ) {
               $requestType->add(self::REQUEST_TYPE);
           }
       }
   }
   ```

- Register this processor in the dependency injection container via `Resources/config/services.yml` file:

  ```yaml
  acme.api.erp.check_erp_request_type:
      class: Acme\Bundle\AppBundle\Api\Processor\CheckErpRequestType
      tags:
          - { name: oro.api.processor, action: get, group: initialize, priority: 250 }
          - { name: oro.api.processor, action: get_list, group: initialize, priority: 250 }
          - { name: oro.api.processor, action: delete, group: initialize, priority: 250 }
          - { name: oro.api.processor, action: delete_list, group: initialize, priority: 250 }
          - { name: oro.api.processor, action: create, group: initialize, priority: 250 }
          - { name: oro.api.processor, action: update, group: initialize, priority: 250 }
          - { name: oro.api.processor, action: get_subresource, group: initialize, priority: 250 }
          - { name: oro.api.processor, action: get_relationship, group: initialize, priority: 250 }
          - { name: oro.api.processor, action: delete_relationship, group: initialize, priority: 250 }
          - { name: oro.api.processor, action: add_relationship, group: initialize, priority: 250 }
          - { name: oro.api.processor, action: update_relationship, group: initialize, priority: 250 }
  ```

- Execute `cache:clear` command to apply the changes and `oro:api:doc:cache:clear` command to build API Sandbox.

That is all. Now you can open [API Sandbox](https://oroinc.com/orocrm/doc/current/dev-guide/web-api#api-sandbox)
and check that it has `ERP Integration` link at the top. Click on this link and try to perform any API request.

To configure the new API use `Resources/config/oro/api_erp.yml` configuration file.

All API processors related to the new API should be registered with `requestType: erp` attribute
for `oro.api.processor` tag, e.g.:

```yaml
    acme.api.erp.do_something:
        class: Acme\Bundle\AppBundle\Api\Processor\DoSomething
        tags:
            - { name: oro.api.processor, action: get, group: initialize, requestType: erp, priority: -10 }
```

For more details about the configuration and processors see [Configuration Reference](./configuration.md),
[Actions](./actions.md) and [Processors](./processors.md).

## Add a Predefined Identifier for API Resource

Imagine that you want to provide an API resource for the current authenticated user. There are several ways to do this:

- [add a custom route](#add-a-custom-route)
- [add a custom controller](#add-a-custom-controller)
- create a model inherited from an User entity and expose it as a separate API resource
- reserve some word, e.g. **mine**, as an predefined identifier of the current authenticated user

The last approach is simplest to implement and more preferred in the most cases, because it gives a possibility
to use such identifier in a resource path, filters and request data.

To implement this approach you need to do the following:

- create a class that implements [EntityIdResolverInterface](../../Request/EntityIdResolverInterface.php), e.g.:

```php
<?php

namespace Oro\Bundle\UserBundle\Api;

use Oro\Bundle\ApiBundle\Request\EntityIdResolverInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Resolves "mine" identifier for User entity.
 * This identifier can be used to identify the current authenticated user.
 */
class MineUserEntityIdResolver implements EntityIdResolverInterface
{
    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return <<<MARKDOWN
**mine** can be used to identify the current authenticated user.
MARKDOWN;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve()
    {
        $user = $this->tokenAccessor->getUser();

        return $user instanceof User
            ? $user->getId()
            : null;
    }
}
```

- register this class as a service and tag it with `oro.api.entity_id_resolver`, e.g.:

```yaml
    oro_user.api.mine_user_entity_id_resolver:
        class: Oro\Bundle\UserBundle\Api\MineUserEntityIdResolver
        arguments:
            - '@oro_security.token_accessor'
        tags:
            - { name: oro.api.entity_id_resolver, id: mine, class: Oro\Bundle\UserBundle\Entity\User }
```

If a predefined identifier should be available only for a specific request type
the [requestType](./request_type.md) attribute of the tag can be used, e.g.:

```yaml
        tags:
            - { name: oro.api.entity_id_resolver, id: mine, class: Oro\Bundle\UserBundle\Entity\User, requestType: json_api }
```

## Add a Computed Field

Sometimes it is required to add to API a field that does not exist in an entity for which API is created.
In this case such field should be added to API via
[Resources/config/oro/api.yml](./configuration.md#fields-configuration-section) and
the [customize_loaded_data](./actions.md#customize_loaded_data-action) action should be used to set a value
of this field.

For example, imagine that a "price" field need to be added to a product API. The following steps show how to do this:

- add the "price" field to the product API via `Resources/config/oro/api.yml`

```yaml
api:
    entities:
        Acme\Bundle\AppBundle\Entity\Product:
            fields:
                price:
                    data_type: money
```

- create a processor for `customize_loaded_data` action that will set a value for the "price" field

```php
<?php

namespace Acme\Bundle\AppBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class ComputeProductPriceField implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data)) {
            return;
        }

        $priceFieldName = $context->getResultFieldName('price');
        if (!$context->isFieldRequested($priceFieldName, $data)) {
            return;
        }

        $productIdFieldName = $context->getResultFieldName('id');
        if (!$productIdFieldName || empty($data[$productIdFieldName])) {
            return;
        }

        $data[$priceFieldName] = $this->loadProductPrice($data[$productIdFieldName]);
        $context->setResult($data);
    }

    /**
     * @param int $productId
     *
     * @return float|null
     */
    private function loadProductPrice($productId)
    {
        // load the product price in this method
    }
}
```

- register the processor in the dependency injection container

```yamp
services:
    acme.api.compute_product_price_field:
        class: Acme\Bundle\AppBundle\Api\Processor\ComputeProductPriceField
        tags:
            - { name: oro.api.processor, action: customize_loaded_data, class: Acme\Bundle\AppBundle\Entity\Product }
```

## Disable HATEOAS

It is not possible to disable [HATEOAS](https://restfulapi.net/hateoas/) via a configuration.
But you can send API request with `noHateoas` value in [X-Include header](./headers.md#existing-x-include-keys)
to exclude HATEOAS links from a response of a particular request.

