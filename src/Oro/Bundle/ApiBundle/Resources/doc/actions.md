Actions
=======

Table of Contents
-----------------
 - [Overview](#overview)
 - Existing actions
    - [**collect_resources** Action](#collect_resources-action)
    - [**get** Action](#get-action)
    - [**get_list** Action](#get_list-action)
    - [**delete** Action](#delete-action)
    - [**delete_list** Action](#delete_list-action)
    - [**create** Action](#create-action)
    - [**update** Action](#update-action)
    - [**customize_loaded_data** Action](#customize_loaded_data-action)
    - [**get_config** Action](#get_config-action)
    - [**get_relation_config** Action](#get_relation_config-action)
    - [**get_metadata** Action](#get_metadata-action)
    - [**normalize_value** Action](#normalize_value-action)
 - [**Context** class](#context-class)
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
| [get](#get-action) | Returns an entity by its identifier |
| [get_list](#get_list-action) | Returns a list of entities |
| [delete](#delete-action) | Deletes an entity by its identifier |
| [delete_list](#delete_list-action) | Deletes a list of entities |
| [customize_loaded_data](#customize_loaded_data-action) | Makes modifications of data loaded by [get](#get-action) or [get_list](#get_list-action) actions |
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

Also [ResourcesLoader](../../Provider/ResourcesLoader.php) was created to make usage of this action as easy as possible.

Example of usage:

```php
/** @var ResourcesLoader $resourcesLoader */
$resourcesLoader = $container->get('oro_api.resources_loader');
$resources = $resourcesLoader->getResources($version, $requestType);
```

get Action
----------

This action is intended to get an entity by its identifier.

The context class: [GetContext](../../Processor/Get/GetContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [GetProcessor](../../Processor/GetProcessor.php).

Existing worker processors: [processors.get.yml](../../Resources/config/processors.get.yml) or run `php app/console oro:api:debug get`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| security_check | Checking whether an access to the requested resource is granted. | If you add a new processor in this group, it should be added in the **security_check** group of actions that execute this action, e.g. look at **security_check** group of [create](#create-action) or [update](#update-action) actions. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| build_query | Building a query that will be used to load data | |
| load_data | Loading data | |
| normalize_data | Converting loaded data into array | In most cases the processors from this group are skipped because most of entities are loaded by the [EntitySerializer](../../../../Component/EntitySerializer/README.md) and it returns already normalized data. For details see [LoadDataByEntitySerializer](../../Processor/Get/LoadDataByEntitySerializer.php). |
| finalize | Final validation of loaded data and adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `getAction` method of [RestApiController](../../Controller/RestApiController.php).

get_list Action
---------------

This action is intended to get a list of entities.

The context class: [GetListContext](../../Processor/GetList/GetListContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [GetListProcessor](../../Processor/GetListProcessor.php).

Existing worker processors: [processors.get_list.yml](../../Resources/config/processors.get_list.yml) or run `php app/console oro:api:debug get_list`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| security_check | Checking whether an access to the requested resource is granted | |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| build_query | Building a query that will be used to load data | |
| load_data | Loading data | |
| normalize_data | Converting loaded data into array | In most cases the processors from this group are skipped because most of entities are loaded by the [EntitySerializer](../../../../Component/EntitySerializer/README.md) and it returns already normalized data. For details see [LoadDataByEntitySerializer](../../Processor/Get/LoadDataByEntitySerializer.php). |
| finalize | Final validation of loaded data and adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `cgetAction` method of [RestApiController](../../Controller/RestApiController.php).

delete Action
-------------

This action is intended to delete an entity by its identifier.

The context class: [DeleteContext](../../Processor/Delete/DeleteContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [DeleteProcessor](../../Processor/DeleteProcessor.php).

Existing worker processors: [processors.delete.yml](../../Resources/config/processors.delete.yml) or run `php app/console oro:api:debug delete`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| security_check | Checking whether an access to the requested resource is granted | |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| build_query | Building a query that will be used to load an entity to be deleted | |
| load_data | Loading an entity that should be deleted and save it in the `result` property of the context | |
| delete_data | Deleting the entity stored in the `result` property of the context | |
| finalize | Adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `deleteAction` method of [RestApiController](../../Controller/RestApiController.php).

delete_list Action
------------------

This action is intended to delete a list of entities.

The entities list is builded based on input filters. Please take into account that at least one filter must be specified, otherwise an error raises.

By default the maximum number of entities that can be deleted by one request is 100. This limit was introduced to minimize impact on the server.
You can change this limit for an entity in `Resources/config/acl.yml`, but please test your limit carefully because a big limit may make a big impact to the server.
An example how to change default limit you can read at [how-to](how_to.md#change-the-maximum-number-of-entities-that-can-be-deleted-by-one-request).

The context class: [DeleteListContext](../../Processor/DeleteList/DeleteListContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [DeleteListProcessor](../../Processor/DeleteListProcessor.php).

Existing worker processors: [processors.delete_list.yml](../../Resources/config/processors.delete_list.yml) or run `php app/console oro:api:debug delete_list`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| security_check | Checking whether an access to the requested resource is granted | |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| build_query | Building a query that will be used to load an entities list to be deleted | |
| load_data | Loading an entities list that should be deleted and save it in the `result` property of the context | |
| delete_data | Deleting the entities list stored in the `result` property of the context | |
| finalize | Adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `deleteListAction` method of [RestApiController](../../Controller/RestApiController.php).

create Action
-------------

This action is intended to create a new entity.

The context class: [CreateContext](../../Processor/Create/CreateContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [CreateProcessor](../../Processor/CreateProcessor.php).

Existing worker processors: [processors.create.yml](../../Resources/config/processors.create.yml) or run `php app/console oro:api:debug create`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| security_check | Checking whether an access to the requested resource is granted | If you add own security processor in the **security_check** group of the [get](#get-action) action, add it in this group as well. It is required because the **VIEW** permission is checked here due to a newly created entity should be returned in response and the **security_check** group of the [get](#get-action) action is disabled by **oro_api.create.load_normalized_entity** processor. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| load_data | Creating an new entity object | |
| transform_data | Building a Symfony Form and using it to transform and validate the request data  | |
| save_data | Validating and persisting an entity | |
| normalize_data | Converting created entity into array | |
| finalize | Adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `postAction` method of [RestApiController](../../Controller/RestApiController.php).

update Action
-------------

This action is intended to update an entity.

The context class: [UpdateContext](../../Processor/Update/UpdateContext.php). Also see [Context](#context-class) class for more details.

The main processor class: [UpdateProcessor](../../Processor/UpdateProcessor.php).

Existing worker processors: [processors.update.yml](../../Resources/config/processors.update.yml) or run `php app/console oro:api:debug update`.

This action has the following processor groups:

| Group Name | Responsibility&nbsp;of&nbsp;Processors | Description |
| --- | --- | --- |
| initialize | Initializing of the context | Also the processors from this group are executed when Data API documentation is generated. |
| security_check | Checking whether an access to the requested resource is granted | If you add own security processor in the **security_check** group of the [get](#get-action) action, add it in this group as well. It is required because the **VIEW** permission is checked here due to updated entity should be returned in response and the **security_check** group of the [get](#get-action) action is disabled by **oro_api.update.load_normalized_entity** processor. |
| normalize_input | Preparing input data to be ready to use by processors from the next groups | |
| load_data | Loading an entity object to be updated | |
| transform_data | Building a Symfony Form and using it to transform and validate the request data  | |
| save_data | Validating and persisting an entity | |
| normalize_data | Converting updated entity into array | |
| finalize | Adding required response headers | |
| normalize_result | Building the action result | The processors from this group are executed even if an exception has been thrown by any processor from previous groups. For implementation details see [RequestActionProcessor](../../Processor/RequestActionProcessor.php). |

Example of usage you can find in the `patchAction` method of [RestApiController](../../Controller/RestApiController.php).

customize_loaded_data Action
----------------------------

This action is intended to make modifications of data loaded by [get](#get-action) or [get_list](#get_list-action) actions.

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

The [Context](../../Processor/Context.php) class is very important because it is used as a superclass for the context classes of such actions as [get](#get-action) and [get_list](#get_list-action).

General methods:

- **getClassName()** - Gets Fully-Qualified Class Name of an entity.
- **setClassName(className)** - Sets Fully-Qualified Class Name of an entity.
- **getRequestHeaders()** - Gets request headers.
- **setRequestHeaders(parameterBag)** - Sets an object that will be used to accessing request headers.
- **getResponseHeaders()** - Gets response headers.
- **setResponseHeaders(parameterBag)** - Sets an object that will be used to accessing response headers.
- **getResponseStatusCode()** - Gets the response status code.
- **setResponseStatusCode(statusCode)** - Sets the response status code.
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
- **hasConfigOfFilters()** - Checks whether an entity has a configuration of filters.
- **getConfigOfFilters()** - Gets a [configuration of filters](../../Config/FiltersConfig.php) for an entity.
- **setConfigOfFilters(config)** - Sets a custom configuration of filters. This method can be used to completely override the default configuration of filters.
- **hasConfigOfSorters()** - Checks whether an entity has a configuration of sorters.
- **getConfigOfSorters()** - Gets a [configuration of sorters](../../Config/SortersConfig.php) for an entity.
- **setConfigOfSorters(config)** - Sets a custom configuration of sorters. This method can be used to completely override the default configuration of sorters.
- **hasConfigOf(configSection)** - Checks whether a configuration of the given section exists.
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
