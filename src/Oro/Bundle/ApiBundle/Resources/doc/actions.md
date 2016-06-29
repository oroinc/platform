Actions
=======

Table of Contents
-----------------
 - [Overview](#overview)
 - Existing actions
    - [**collect_resources** Action](#collect_resources-action)
    - [**collect_subresources** Action](#collect_subresources-action)
    - [**get** Action](#get-action)
    - [**get_list** Action](#get_list-action)
    - [**delete** Action](#delete-action)
    - [**delete_list** Action](#delete_list-action)
    - [**create** Action](#create-action)
    - [**update** Action](#update-action)
    - [**get_subresource** Action](#get_subresource-action)
    - [**get_relationship** Action](#get_relationship-action)
    - [**update_relationship** Action](#update_relationship-action)
    - [**add_relationship** Action](#add_relationship-action)
    - [**delete_relationship** Action](#delete_relationship-action)
    - [**customize_loaded_data** Action](#customize_loaded_data-action)
    - [**get_config** Action](#get_config-action)
    - [**get_relation_config** Action](#get_relation_config-action)
    - [**get_metadata** Action](#get_metadata-action)
    - [**normalize_value** Action](#normalize_value-action)
 - [**Context** class](#context-class)
 - [**SubresourceContext** class](#subresourcecontext-class)
 - [Creating new action](#creating-new-action)

Overview
--------

The action is a set of processors intended to process some request.

Each action has two required elements:

- **context** - an object that is used to store input and output data and share data between processors.
- **main processor** - the main entry point for an action. This class is responsible for creating the context and executing all worker processors.

More details about these elements you can find in the [Creating new action](#creating-new-action) section.

The following table shows all actions provided out of the box:

| Action Name           | Description |
| ---                   | --- |
| [collect_resources](#collect_resources-action) | Returns a list of all resources accessible through Data API |
| [collect_subresources](#collect_subresources-action) | Returns a list of all sub-resources accessible through Data API for a given entity type |
| [get](#get-action) | Returns an entity by its identifier |
| [get_list](#get_list-action) | Returns a list of entities |
| [delete](#delete-action) | Deletes an entity by its identifier |
| [delete_list](#delete_list-action) | Deletes a list of entities |
| [create](#create-action) | Creates a new entity |
| [update](#update-action) | Updates an existing entity |
| [get_subresource](#get_subresource-action) | Returns a list of related entities represented by a relationship |
| [get_relationship](#get_relationship-action) | Returns a relationship data |
| [update_relationship](#update_relationship-action) | Updates "to-one" relationship and completely replaces all members of "to-many" relationship |
| [add_relationship](#add_relationship-action) | Adds one or several entities to a relationship. This action is applicable only for "to-many" relationships |
| [delete_relationship](#delete_relationship-action) | Deletes one or several entities from a relationship. This action is applicable only for "to-many" relationships |
| [customize_loaded_data](#customize_loaded_data-action) | Makes modifications of data loaded by [get](#get-action), [get_list](#get_list-action) and [get_subresource](#get_subresource-action) actions |
| [get_config](#get_config-action) | Returns a configuration of an entity |
| [get_relation_config](#get_relation_config-action) | Returns a configuration of an entity if it is used in a relationship |
| [get_metadata](#get_metadata-action) | Returns a metadata of an entity |
| [normalize_value](#normalize_value-action) | Converts a value to a requested data type |

Please see [processors](./processors.md) section for more details about how to create a processor.

Also you can use the [oro:api:debug](./debug_commands.md#oroapidebug) command to see all actions and processors.

collect_resources Action
------------------------

This action is intended to get a list of all resources accessible through Data API.

The context class: [CollectResourcesContext](../../Processor/CollectResources/CollectResourcesContext.php).

The main processor class: [CollectResourcesProcessor](../../Processor/CollectResourcesProcessor.php).

Existing worker processors: [processors.collect_resources.yml](../../Resources/config/processors.collect_resources.yml) or run `php app/console oro:api:debug collect_resources`.

Also [ResourcesProvider](../../Provider/ResourcesProvider.php) was created to make usage of this action as easy as possible.

Example of usage:

```php
/** @var ResourcesProvider $resourcesProvider */
$resourcesProvider = $container->get('oro_api.resources_provider');
// get all Data API resources
$resources = $resourcesProvider->getResources($version, $requestType);
// check whether an entity type is accessible through Data API
$isAccessible = $resourcesProvider->isResourceAccessible($entityClass, $version, $requestType);
```

collect_subresources Action
---------------------------

This action is intended to get a list of all sub-resources accessible through Data API for a given entity type.

The context class: [CollectSubresourcesContext](../../Processor/CollectSubresources/CollectSubresourcesContext.php).

The main processor class: [CollectSubresourcesProcessor](../../Processor/CollectSubresourcesProcessor.php).

Existing worker processors: [processors.collect_subresources.yml](../../Resources/config/processors.collect_subresources.yml) or run `php app/console oro:api:debug collect_subresources`.

Also [SubresourcesProvider](../../Provider/SubresourcesProvider.php) was created to make usage of this action as easy as possible.

Example of usage:

```php
/** @var SubresourcesProvider $subresourcesProvider */
$subresourcesProvider = $container->get('oro_api.subresources_provider');
// get all sub-resources for a given entity
$entitySubresources = $subresourcesProvider->getSubresources($entityClass, $version, $requestType);
```

get Action
----------

This action is intended to get an entity by its identifier. More details you can find in [Fetching Data](http://jsonapi.org/format/#fetching) section of JSON.API specification.

The route name for REST API: `oro_rest_api_get`.

The URL template for REST API: `/api/{entity}/{id}`.

The HTTP method for REST API: `GET`.

The context class: [GetContext](../../Processor/Get/GetContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [GetProcessor](../../Processor/GetProcessor.php).

Existing worker processors: [processors.get.yml](../../Resources/config/processors.get.yml) or run `php app/console oro:api:debug get`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| security_check | Checking whether an access to the requested resource is granted. | If you add a new processor in this group, it should be added in the **security_check** group of actions that execute this action, e.g. look at **security_check** group of [create](#create-action) or [update](#update-action) actions. |
| build_query | Building a query that will be used to load data | |
| load_data | Loading data | |
| normalize_data | Converting loaded data into array | In most cases the processors from this group are skipped because most of entities are loaded by the [EntitySerializer](../../../../Component/EntitySerializer/README.md) and it returns already normalized data. For details see [LoadEntityByEntitySerializer](../../Processor/Shared/LoadEntityByEntitySerializer.php). |
| finalize | Final validation of loaded data and adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `getAction` method of [RestApiController](../../Controller/RestApiController.php).

get_list Action
---------------

This action is intended to get a list of entities. More details you can find in [Fetching Data](http://jsonapi.org/format/#fetching) section of JSON.API specification.

The route name for REST API: `oro_rest_api_cget`.

The URL template for REST API: `/api/{entity}`.

The HTTP method for REST API: `GET`.

The context class: [GetListContext](../../Processor/GetList/GetListContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [GetListProcessor](../../Processor/GetListProcessor.php).

Existing worker processors: [processors.get_list.yml](../../Resources/config/processors.get_list.yml) or run `php app/console oro:api:debug get_list`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| security_check | Checking whether an access to the requested resource is granted | |
| build_query | Building a query that will be used to load data | |
| load_data | Loading data | |
| normalize_data | Converting loaded data into array | In most cases the processors from this group are skipped because most of entities are loaded by the [EntitySerializer](../../../../Component/EntitySerializer/README.md) and it returns already normalized data. For details see [LoadEntitiesByEntitySerializer](../../Processor/Shared/LoadEntitiesByEntitySerializer.php). |
| finalize | Final validation of loaded data and adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `cgetAction` method of [RestApiController](../../Controller/RestApiController.php).

delete Action
-------------

This action is intended to delete an entity by its identifier. More details you can find in [Deleting Resources](http://jsonapi.org/format/#crud-deleting) section of JSON.API specification.

The route name for REST API: `oro_rest_api_delete`.

The URL template for REST API: `/api/{entity}/{id}`.

The HTTP method for REST API: `DELETE`.

The context class: [DeleteContext](../../Processor/Delete/DeleteContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [DeleteProcessor](../../Processor/DeleteProcessor.php).

Existing worker processors: [processors.delete.yml](../../Resources/config/processors.delete.yml) or run `php app/console oro:api:debug delete`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| security_check | Checking whether an access to the requested resource is granted | |
| build_query | Building a query that will be used to load an entity to be deleted | |
| load_data | Loading an entity that should be deleted and save it in the `result` property of the context | |
| delete_data | Deleting the entity stored in the `result` property of the context | |
| finalize | Adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `deleteAction` method of [RestApiController](../../Controller/RestApiController.php).

delete_list Action
------------------

This action is intended to delete a list of entities.

The entities list is built based on input filters. Please take into account that at least one filter must be specified, otherwise an error raises.

By default the maximum number of entities that can be deleted by one request is 100. This limit was introduced to minimize impact on the server.
You can change this limit for an entity in `Resources/config/acl.yml`, but please test your limit carefully because a big limit may make a big impact to the server.
An example how to change default limit you can read at [how-to](how_to.md#change-the-maximum-number-of-entities-that-can-be-deleted-by-one-request).

The route name for REST API: `oro_rest_api_cdelete`.

The URL template for REST API: `/api/{entity}`.

The HTTP method for REST API: `DELETE`.

The context class: [DeleteListContext](../../Processor/DeleteList/DeleteListContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [DeleteListProcessor](../../Processor/DeleteListProcessor.php).

Existing worker processors: [processors.delete_list.yml](../../Resources/config/processors.delete_list.yml) or run `php app/console oro:api:debug delete_list`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| security_check | Checking whether an access to the requested resource is granted | |
| build_query | Building a query that will be used to load an entities list to be deleted | |
| load_data | Loading an entities list that should be deleted and save it in the `result` property of the context | |
| delete_data | Deleting the entities list stored in the `result` property of the context | |
| finalize | Adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `deleteListAction` method of [RestApiController](../../Controller/RestApiController.php).

create Action
-------------

This action is intended to create a new entity. More details you can find in [Creating Resources](http://jsonapi.org/format/#crud-creating) section of JSON.API specification.

The route name for REST API: `oro_rest_api_post`.

The URL template for REST API: `/api/{entity}`.

The HTTP method for REST API: `POST`.

The context class: [CreateContext](../../Processor/Create/CreateContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [CreateProcessor](../../Processor/CreateProcessor.php).

Existing worker processors: [processors.create.yml](../../Resources/config/processors.create.yml) or run `php app/console oro:api:debug create`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| security_check | Checking whether an access to the requested resource is granted | If you add own security processor in the **security_check** group of the [get](#get-action) action, add it in this group as well. It is required because the **VIEW** permission is checked here due to a newly created entity should be returned in response and the **security_check** group of the [get](#get-action) action is disabled by **oro_api.create.load_normalized_entity** processor. |
| load_data | Creating an new entity object | |
| transform_data | Building a Symfony Form and using it to transform and validate the request data  | |
| save_data | Validating and persisting an entity | |
| normalize_data | Converting created entity into array | |
| finalize | Adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `postAction` method of [RestApiController](../../Controller/RestApiController.php).

update Action
-------------

This action is intended to update an entity. More details you can find in [Updating Resources](http://jsonapi.org/format/#crud-updating) section of JSON.API specification.

The route name for REST API: `oro_rest_api_patch`.

The URL template for REST API: `/api/{entity}/{id}`.

The HTTP method for REST API: `PATCH`.

The context class: [UpdateContext](../../Processor/Update/UpdateContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [UpdateProcessor](../../Processor/UpdateProcessor.php).

Existing worker processors: [processors.update.yml](../../Resources/config/processors.update.yml) or run `php app/console oro:api:debug update`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| security_check | Checking whether an access to the requested resource is granted | If you add own security processor in the **security_check** group of the [get](#get-action) action, add it in this group as well. It is required because the **VIEW** permission is checked here due to updated entity should be returned in response and the **security_check** group of the [get](#get-action) action is disabled by **oro_api.update.load_normalized_entity** processor. |
| load_data | Loading an entity object to be updated | |
| transform_data | Building a Symfony Form and using it to transform and validate the request data  | |
| save_data | Validating and persisting an entity | |
| normalize_data | Converting updated entity into array | |
| finalize | Adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `patchAction` method of [RestApiController](../../Controller/RestApiController.php).

get_subresource Action
----------------------

This action is intended to get an entity (for "to-one" relationship) or a list of entities (for "to-many" relationship) connected to a given entity by a given association. More details you can find in [Fetching Resources](http://jsonapi.org/format/#fetching-resources) section of JSON.API specification.

The route name for REST API: `oro_rest_api_get_subresource`.

The URL template for REST API: `/api/{entity}/{id}/{association}`.

The HTTP method for REST API: `GET`.

The context class: [GetSubresourceContext](../../Processor/Subresource/GetSubresource/GetSubresourceContext.php). Also see [SubresourceContext](#subresourcecontext-class) class for more details.

The main processor class: [GetSubresourceProcessor](../../Processor/Subresource/GetSubresourceProcessor.php).

Existing worker processors: [processors.get_subresource.yml](../../Resources/config/processors.get_subresource.yml) or run `php app/console oro:api:debug get_subresource`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| security_check | Checking whether an access to the requested resource is granted | |
| build_query | Building a query that will be used to load data | |
| load_data | Loading data | |
| normalize_data | Converting loaded data into array | In most cases the processors from this group are skipped because most of entities are loaded by the [EntitySerializer](../../../../Component/EntitySerializer/README.md) and it returns already normalized data. For details see [LoadEntityByEntitySerializer](../../Processor/Shared/LoadEntityByEntitySerializer.php) and [LoadEntitiesByEntitySerializer](../../Processor/Shared/LoadEntitiesByEntitySerializer.php). |
| finalize | Final validation of loaded data and adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `getAction` method of [RestApiSubresourceController](../../Controller/RestApiSubresourceController.php).

get_relationship Action
-----------------------

This action is intended to get an entity identifier (for "to-one" relationship) or a list of entities' identifiers (for "to-many" relationship) connected to a given entity by a given association. More details you can find in [Fetching Relationships](http://jsonapi.org/format/#fetching-relationships) section of JSON.API specification.

The route name for REST API: `oro_rest_api_get_relationship`.

The URL template for REST API: `/api/{entity}/{id}/relationships/{association}`.

The HTTP method for REST API: `GET`.

The context class: [GetRelationshipContext](../../Processor/Subresource/GetRelationship/GetRelationshipContext.php). Also see [SubresourceContext](#subresourcecontext-class) class for more details.

The main processor class: [GetRelationshipProcessor](../../Processor/Subresource/GetRelationshipProcessor.php).

Existing worker processors: [processors.get_relationship.yml](../../Resources/config/processors.get_relationship.yml) or run `php app/console oro:api:debug get_relationship`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| security_check | Checking whether an access to the requested resource is granted | |
| build_query | Building a query that will be used to load data | |
| load_data | Loading data | |
| normalize_data | Converting loaded data into array | In most cases the processors from this group are skipped because most of entities are loaded by the [EntitySerializer](../../../../Component/EntitySerializer/README.md) and it returns already normalized data. For details see [LoadEntityByEntitySerializer](../../Processor/Shared/LoadEntityByEntitySerializer.php) and [LoadEntitiesByEntitySerializer](../../Processor/Shared/LoadEntitiesByEntitySerializer.php). |
| finalize | Final validation of loaded data and adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `getAction` method of [RestApiRelationshipController](../../Controller/RestApiRelationshipController.php).

update_relationship Action
--------------------------

This action is intended to change an entity (for "to-one" relationship) or completely replace all entities (for "to-many" relationship) connected to a given entity by a given association. More details you can find in [Updating Relationships](http://jsonapi.org/format/#crud-updating-relationships) section of JSON.API specification.

The route name for REST API: `oro_rest_api_patch_relationship`.

The URL template for REST API: `/api/{entity}/{id}/relationships/{association}`.

The HTTP method for REST API: `PATCH`.

The context class: [UpdateRelationshipContext](../../Processor/Subresource/UpdateRelationship/UpdateRelationshipContext.php). Also see [SubresourceContext](#subresourcecontext-class) class for more details.

The main processor class: [UpdateRelationshipProcessor](../../Processor/Subresource/UpdateRelationshipProcessor.php).

Existing worker processors: [processors.update_relationship.yml](../../Resources/config/processors.update_relationship.yml) or run `php app/console oro:api:debug update_relationship`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| security_check | Checking whether an access to the requested resource is granted | |
| load_data | Loading an entity object to be updated | |
| transform_data | Building a Symfony Form and using it to transform and validate the request data  | |
| save_data | Validating and persisting an entity | |
| finalize | Adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `patchAction` method of [RestApiRelationshipController](../../Controller/RestApiRelationshipController.php).

add_relationship Action
-----------------------

This action is intended to add one or several entities to a "to-many" relationship. More details you can find in [Updating Relationships](http://jsonapi.org/format/#crud-updating-relationships) section of JSON.API specification.

The route name for REST API: `oro_rest_api_post_relationship`.

The URL template for REST API: `/api/{entity}/{id}/relationships/{association}`.

The HTTP method for REST API: `POST`.

The context class: [AddRelationshipContext](../../Processor/Subresource/AddRelationship/AddRelationshipContext.php). Also see [SubresourceContext](#subresourcecontext-class) class for more details.

The main processor class: [AddRelationshipProcessor](../../Processor/Subresource/AddRelationshipProcessor.php).

Existing worker processors: [processors.add_relationship.yml](../../Resources/config/processors.add_relationship.yml) or run `php app/console oro:api:debug add_relationship`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| security_check | Checking whether an access to the requested resource is granted | |
| load_data | Loading an entity object to be updated | |
| transform_data | Building a Symfony Form and using it to transform and validate the request data  | |
| save_data | Validating and persisting an entity | |
| finalize | Adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `postAction` method of [RestApiRelationshipController](../../Controller/RestApiRelationshipController.php).

delete_relationship Action
--------------------------

This action is intended to remove one or several entities from a "to-many" relationship. More details you can find in [Updating Relationships](http://jsonapi.org/format/#crud-updating-relationships) section of JSON.API specification.

The route name for REST API: `oro_rest_api_delete_relationship`.

The URL template for REST API: `/api/{entity}/{id}/relationships/{association}`.

The HTTP method for REST API: `POST`.

The context class: [AddRelationshipContext](../../Processor/Subresource/AddRelationship/AddRelationshipContext.php). Also see [SubresourceContext](#subresourcecontext-class) class for more details.

The main processor class: [AddRelationshipProcessor](../../Processor/Subresource/AddRelationshipProcessor.php).

Existing worker processors: [processors.delete_relationship.yml](../../Resources/config/processors.delete_relationship.yml) or run `php app/console oro:api:debug delete_relationship`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| security_check | Checking whether an access to the requested resource is granted | |
| load_data | Loading an entity object to be updated | |
| transform_data | Building a Symfony Form and using it to transform and validate the request data  | |
| save_data | Validating and persisting an entity | |
| finalize | Adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `deleteAction` method of [RestApiRelationshipController](../../Controller/RestApiRelationshipController.php).

customize_loaded_data Action
----------------------------

This action is intended to make modifications of data loaded by [get](#get-action), [get_list](#get_list-action) and [get_subresource](#get_subresource-action) actions.

The context class: [CustomizeLoadedDataContext](../../Processor/CollectResources/CustomizeLoadedDataContext.php).

The main processor class: [CustomizeLoadedDataProcessor](../../Processor/CustomizeLoadedDataProcessor.php).

There are no worker processors in ApiBundle. To see existing worker processors from other bundles run `php app/console oro:api:debug customize_loaded_data`.

An example of own processor to modify loaded data:

```php
<?php

namespace Acme\Bundle\UserBundle\Api\Processor;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "fullName" field for User entity.
 */
class ComputeUserFullName implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $data = $context->getResult();
        if (!empty($data)
            && empty($data['fullName'])
            && array_key_exists('firstName', $data)
            && array_key_exists('lastName', $data)
        ) {
            $data['fullName'] = $data['firstName'] . ' ' . $data['lastName'];
            $context->setResult($data);
        }
    }
}
```

```yaml
    acme.api.customize_loaded_data.compute_user_full_name:
        class: Acme\Bundle\UserBundle\Api\Processor\ComputeUserFullName
        tags:
            - { name: oro.api.processor, action: customize_loaded_data, class: "Oro\Bundle\UserBundle\Entity\User" }
```

get_config Action
-----------------

This action is intended to get a configuration of an entity.

The context class: [ConfigContext](../../Processor/Config/ConfigContext.php).

The main processor class: [ConfigProcessor](../../Processor/Config/ConfigProcessor.php).

Existing worker processors: [processors.get_config.yml](../../Resources/config/processors.get_config.yml) or run `php app/console oro:api:debug get_config`.

Also [ConfigProvider](../../Provider/ConfigProvider.php) was created to make usage of this action as easy as possible.

Example of usage:

```php
/** @var ConfigProvider $configProvider */
$configProvider = $container->get('oro_api.config_provider');
$config = $configProvider->getConfig($entityClassName, $version, $requestType, $configExtras);
```

get_relation_config Action
--------------------------

This action is intended to get a configuration of an entity if it is used in a relationship.

The context class: [RelationConfigContext](../../Processor/Config/GetRelationConfig/RelationConfigContext.php).

The main processor class: [RelationConfigProcessor](../../Processor/Config/RelationConfigProcessor.php).

Existing worker processors: [processors.get_config.yml](../../Resources/config/processors.get_config.yml) or run `php app/console oro:api:debug get_relation_config`.

Also [RelationConfigProvider](../../Provider/RelationConfigProvider.php) was created to make usage of this action as easy as possible.

Example of usage:

```php
/** @var RelationConfigProvider $configProvider */
$configProvider = $container->get('oro_api.relation_config_provider');
$config = $configProvider->getRelationConfig($entityClassName, $version, $requestType, $configExtras);
```

get_metadata Action
-------------------

This action is intended to get a metadata of an entity.

The context class: [MetadataContext](../../Processor/GetMetadata/MetadataContext.php).

The main processor class: [MetadataProcessor](../../Processor/MetadataProcessor.php).

Existing worker processors: [processors.get_metadata.yml](../../Resources/config/processors.get_metadata.yml) or run `php app/console oro:api:debug get_metadata`.

Also [MetadataProvider](../../Provider/MetadataProvider.php) was created to make usage of this action as easy as possible.

Example of usage:

```php
/** @var MetadataProvider $metadataProvider */
$metadataProvider = $container->get('oro_api.metadata_provider');
$metadata = $metadataProvider->getMetadata($entityClassName, $version, $requestType, $metadataExtras, $entityConfig);
```

normalize_value Action
----------------------

This action is intended to convert a value to a requested data type.

The context class: [NormalizeValueContext](../../Processor/NormalizeValue/NormalizeValueContext.php).

The main processor class: [NormalizeValueProcessor](../../Processor/NormalizeValueProcessor.php).

Existing worker processors: [processors.normalize_value.yml](../../Resources/config/processors.normalize_value.yml) or run `php app/console oro:api:debug normalize_value`.

Also [ValueNormalizer](../../Request/ValueNormalizer.php) was created to make usage of this action as easy as possible.

Example of usage:

```php
/** @var ValueNormalizer $valueNormalizer */
$valueNormalizer = $container->get('oro_api.metadata_provider');
$normalizedValue = $valueNormalizer->normalizeValue($value, $dataType, $requestType);
```

Context class
-------------

The [Context](../../Processor/Context.php) class is very important because it is used as a superclass for the context classes of CRUD actions such as [get](#get-action), [get_list](#get_list-action), [create](#create-action), [update](#update-action), [delete](#delete-action) and [delete_list](#delete_list-action).

General methods:

- **getClassName()** - Gets Fully-Qualified Class Name of an entity.
- **setClassName(className)** - Sets Fully-Qualified Class Name of an entity.
- **getRequestHeaders()** - Gets request headers.
- **setRequestHeaders(parameterBag)** - Sets an object that will be used to accessing request headers.
- **getResponseHeaders()** - Gets response headers.
- **setResponseHeaders(parameterBag)** - Sets an object that will be used to accessing response headers.
- **getResponseStatusCode()** - Gets the response status code.
- **setResponseStatusCode(statusCode)** - Sets the response status code.
- **isSuccessResponse()** - Indicates whether a result document represents a success response.
- **getFilters()** - Gets a [list of filters](../../Filter/FilterCollection.php) is used to add additional restrictions to a query is used to get entity data.
- **getFilterValues()** - Gets a collection of the [FilterValue](../../Filter/FilterValue.php) objects that contains all incoming filters.
- **setFilterValues(accessor)** - Sets an [object](../../Filter/FilterValueAccessorInterface.php) that will be used to accessing incoming filters.
- **hasQuery()** - Checks whether a query is used to get result data exists.
- **getQuery()** - Gets a query is used to get result data.
- **setQuery(query)** - Sets a query is used to get result data.
- **getCriteria()** - Gets the [Criteria](../../Collection/Criteria.php) object is used to add additional restrictions to a query is used to get result data.
- **setCriteria(criteria)** - Sets the [Criteria](../../Collection/Criteria.php) object is used to add additional restrictions to a query is used to get result data.
- **hasErrors()** - Checks whether any error happened during the processing of an action.
- **getErrors()** - Gets all [errors](../../Model/Error.php) happened during the processing of an action.
- **addError(error)** - Registers an [error](../../Model/Error.php).
- **resetErrors()** - Removes all errors.

Entity configuration related methods:

- **getConfigExtras()** - Gets a list of [requests for configuration data](../../Config/ConfigExtraInterface.php).
- **setConfigExtras(extras)** - Sets a list of requests for configuration data.
- **hasConfigExtra(extraName)** - Checks whether some configuration data is requested.
- **getConfigExtra(extraName)** - Gets a request for configuration data by its name.
- **addConfigExtra(extra)** - Adds a request for some configuration data.
- **removeConfigExtra(extraName)** - Removes a request for some configuration data.
- **getConfigSections()** - Gets names of all requested [configuration sections](../../Config/ConfigExtraSectionInterface.php).
- **hasConfig()** - Checks whether a configuration of an entity exists.
- **getConfig()** - Gets a [configuration of an entity](../../Config/EntityDefinitionConfig.php).
- **setConfig(config)** - Sets a custom configuration of an entity. This method can be used to completely override the default configuration of an entity.
- **hasConfigOfFilters(initialize)** - Checks whether an entity has a configuration of filters.
- **getConfigOfFilters()** - Gets a [configuration of filters](../../Config/FiltersConfig.php) for an entity.
- **setConfigOfFilters(config)** - Sets a custom configuration of filters. This method can be used to completely override the default configuration of filters.
- **hasConfigOfSorters(initialize)** - Checks whether an entity has a configuration of sorters.
- **getConfigOfSorters()** - Gets a [configuration of sorters](../../Config/SortersConfig.php) for an entity.
- **setConfigOfSorters(config)** - Sets a custom configuration of sorters. This method can be used to completely override the default configuration of sorters.
- **hasConfigOf(configSection, initialize)** - Checks whether a configuration of the given section exists.
- **getConfigOf(configSection)** - Gets a configuration from the given section.
- **setConfigOf(configSection, config)** - Sets a configuration for the given section. This method can be used to completely override the default configuration for the given section.

Entity metadata related methods:

- **getMetadataExtras()** - Gets a list of [requests for additional metadata info](../../Metadata/MetadataExtraInterface.php).
- **setMetadataExtras(extras)** - Sets a list of requests for additional metadata info.
- **hasMetadataExtra()** - Checks whether some additional metadata info is requested.
- **addMetadataExtra(extra)** - Adds a request for some additional metadata info.
- **removeMetadataExtra(extraName)** - Removes a request for some additional metadata info.
- **hasMetadata()** - Checks whether metadata of an entity exists.
- **getMetadata()** - Gets [metadata](../../Metadata/EntityMetadata.php) of an entity.
- **setMetadata(metadata)** - Sets metadata of an entity. This method can be used to completely override the default metadata of an entity.

SubresourceContext class
------------------------

The [SubresourceContext](../../Processor/Subresource/SubresourceContext.php) class is used as a superclass for the context classes of sub-resources related actions such as [get_subresource](#get_subresource-action), [get_relationship](#get_relationship-action), [update_relationship](#update_relationship-action), [add_relationship](#add_relationship-action) and [delete_relationship](#delete_relationship-action). In additional to the [Context](#context-class) class, this class provides methods to work with parent entities.

General methods:

- **getParentClassName()** - Gets Fully-Qualified Class Name of the parent entity.
- **setParentClassName(className)** - Sets Fully-Qualified Class Name of the parent entity.
- **getParentId()** - Gets an identifier of the parent entity.
- **setParentId(parentId)** - Sets an identifier of the parent entity.
- **getAssociationName()** - Gets an association name represented a relationship.
- **setAssociationName(associationName)** - Sets an association name represented a relationship.
- **isCollection()** - Indicates an association represents "to-many" or "to-one" relation.
- **setIsCollection(value)** - Sets a flag indicates whether an association represents "to-many" or "to-one" relation.
- **hasParentEntity()** - Checks whether the parent entity exists in the context.
- **getParentEntity()** - Gets the parent entity object.
- **setParentEntity(parentEntity)** - Sets the parent entity object.

Parent entity configuration related methods:

- **getParentConfigExtras()** - Gets a list of [requests for configuration data](../../Config/ConfigExtraInterface.php) for the parent entity.
- **setParentConfigExtras(extras)** - Sets a list of requests for configuration data for the parent entity.
- **hasParentConfig()** - Checks whether a configuration of the parent entity exists.
- **getParentConfig()** - Gets a [configuration of the parent entity](../../Config/EntityDefinitionConfig.php).
- **setParentConfig(config)** - Sets a custom configuration of the parent entity. This method can be used to completely override the default configuration of the parent entity.

Parent entity metadata related methods:

- **getParentMetadataExtras()** - Gets a list of [requests for additional metadata info](../../Metadata/MetadataExtraInterface.php) for the parent entity.
- **setParentMetadataExtras(extras)** - Sets a list of requests for additional metadata info for the parent entity.
- **hasParentMetadata()** - Checks whether metadata of the parent entity exists.
- **getParentMetadata()** - Gets [metadata](../../Metadata/EntityMetadata.php) of the parent entity.
- **setParentMetadata(metadata)** - Sets metadata of the parent entity. This method can be used to completely override the default metadata of the parent entity.

Creating new action
-------------------

To create a new action you need to create two classes:

- **context** - This class represents an context in scope of which an action is executed. Actually an instance of this class is used to store input and output data and share data between processors. This class must extend [ApiContext](../../Processor/ApiContext.php). Also, depending on your needs, you can use another classes derived from the [ApiContext](../../Processor/ApiContext.php), for example [Context](../../Processor/Context.php), [SingleItemContext](../../Processor/SingleItemContext.php) or [ListContext](../../Processor/ListContext.php).
- **main processor** - This class is the main entry point for an action and responsible for creating an instance of the context class and executing all worker processors. This class must extend [ActionProcessor](../../../../Component/ChainProcessor/ActionProcessor.php) and implement the `createContextObject` method. Also, depending on your needs, you can use another classes derived from the [ActionProcessor](../../../../Component/ChainProcessor/ActionProcessor.php), for example [RequestActionProcessor](../../Processor/RequestActionProcessor.php).

```php
<?php

namespace Acme\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ApiContext;

class MyActionContext extends ApiContext
{
}
```

```php
<?php

namespace Acme\Bundle\ProductBundle\Api\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;

class MyActionProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new MyActionContext();
    }
}
```

Also you need to register your processor in the dependency injection container:

```yaml
    acme.my_action.processor:
        class: Acme\Bundle\ProductBundle\Api\Processor\MyActionProcessor
        public: false
        arguments:
            - @oro_api.processor_bag
            - my_action # the name of an action
```

In case if you need to create groups for your action, they should be registered in the ApiBundle configuration. To do this just add `Resources\config\oro\app.yml` to your bundle, for example:

```yaml
oro_api:
    actions:
        my_action:
            processing_groups:
                initialize:
                    priority: -10
                load_data:
                    priority: -20
                finalize:
                    priority: -30
```

Please note that the `priority` attribute is used to control the order in which groups of processors are executed. The highest the priority, the earlier a group of processors is executed. Default value is 0. The possible range is from -254 to 252. Details about creating processors you can find in the [processors](./processors.md#creating-a-processor) section.
