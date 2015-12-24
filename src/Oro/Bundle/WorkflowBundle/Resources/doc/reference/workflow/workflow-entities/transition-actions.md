Transition Actions
=======================

Table of Contents
-----------------
 - [Add Custom Action](#add-custom-action)
 - [Configuration Syntax](#configuration-syntax)
 - [Assign Value](#assign-value)
 - [Assign Active User](#assign-active-user)
 - [Unset Value](#unset-value)
 - [Create Object](#create-object)
 - [Create Entity](#create-entity)
 - [Remove Entity](#remove-entity)
 - [Create Related Entity](#create-related-entity)
 - [Find Entity](#find-entity)
 - [Format Name](#format-name)
 - [Format String](#format-string)
 - [Call Method](#call-method)
 - [Create Date](#create-date)
 - [Create Date Time](#create-date-time)
 - [Start Workflow](#start-workflow)
 - [Transit Workflow](#transit-workflow)
 - [Redirect](#redirect)
 - [Tree Executor](#tree-executor)
 - [Foreach](#foreach)
 - [Configurable](#configurable)
 - [Flash Message](#flash-message)

Add Custom Action
----------------------

To add custom action add a service to DIC with tag "oro_workflow.action", for example:

```
parameters:
    oro_workflow.action.close_workflow.class: Oro\Bundle\WorkflowBundle\Model\Action\CloseWorkflow
services:
    oro_workflow.action.close_workflow:
        class: %oro_workflow.action.close_workflow.class%
        tags:
            - { name: oro_workflow.action, alias: close_workflow }
```

Symbol "|" in alias can be used to have several aliases. Note that service class must implement
Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface.

Configuration Syntax
--------------------

Each action can be optionally configured with condition. It allows to implement more sufficient logic in
transitions definitions. If condition is not satisfied action won't be executed.

If flag "break_on_failure" is specified action throws an exception on error, otherwise logs error using standard
logger.

See syntax examples:

**Full Configuration Example**

```
- @alias_of_action:
    conditions:
        # optional condition configuration
    parameters:
        - some_parameters: some_value
        # other parameters of action
    break_on_failure: boolean # by default false
```

**Short Configuration Example**
```
- @alias_of_action:
    - some_parameters: some_value
    # other parameters of action
```

Assign Value
------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\AssignValue

**Alias:** assign_value

**Description:** Sets value of attribute from source

**Parameters:**
 - attribute / 0 - attribute where value should be set;
 - value / 1 - value that should be set.

**Configuration Example**
```
- @assign_value:
    conditions:
        # optional condition configuration
    parameters: [$call_successfull, true]

OR

- @assign_value:
    parameters:
        attribute: $call_successfull
        value: true
OR

- @assign_value: [$call_successfull, true]
```

Assign Active User
------------------

**Class** Oro\Bundle\WorkflowBundle\Model\Action\AssignActiveUser

**Alias** assign_active_user or get_active_user

**Description** Set currently logged in user to attribute

**Parameters**
 - attribute / 0 - attribute where value should be set;

**Configuration Example**
```
- @assign_active_user: $opportunity_owner

OR

- @assign_active_user:
    parameters:
        attribute: $opportunity_owner
```

Unset Value
------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\UnsetValue

**Alias:** unset_value

**Description:** Unsets value of attribute from source

**Parameters:**
 - attribute / 0 - attribute where value should be set;

**Configuration Example**
```
- @unset_value:
    conditions:
        # optional condition configuration
    parameters: [$call_successfull]

OR

- @unset_value:
    parameters:
        attribute: $call_successfull
OR

- @unset_value: [$call_successfull]
```

Create Object
-------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\CreateObject

**Alias:** create_object

**Description:** Creates object with specified class and data, and sets it as attribute value.

**Parameters:**
 - class - class name of created object;
 - arguments - array of object constructor arguments;
 - attribute - attribute that will contain entity instance;
 - data - array of data that should be set to entity.

**Configuration Example**
```
- @create_object:
    class: \DateTimeZone
    arguments: ['UTC']
    attribute: $.result.timezone

OR

- @create_object:
    class: \DateTime
    arguments: ['2014-04-01']
    data:
        timezone: $.result.timezone
    attribute: $.result.release_date
```

Create Entity
-------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\CreateEntity

**Alias:** create_entity

**Description:** Creates entity with specified class and data, and sets it as attribute value.

**Parameters:**
 - class - fully qualified class name of created entity;
 - attribute - attribute that will contain entity instance;
 - flush - when flush in DB should be performed.
           Immediately after entity creation if ``true`` or later if ``false`` (default value: false);
 - data - array of data that should be set to entity.

**Configuration Example**
```
- @create_entity:
    conditions:
        # optional condition configuration
    parameters:
        class: Acme\Bundle\DemoWorkflowBundle\Entity\PhoneConversation
        attribute: $conversation
        data:
            result: $conversation_result
            comment: $conversation_comment
            successful: $conversation_successful
            call: $managed_entity

OR

- @create_entity:
    class: Acme\Bundle\DemoWorkflowBundle\Entity\PhoneConversation
    attribute: $conversation
    flush: true # entity will be flushed to DB immediately after creation
    data:
        result: $conversation_result
        comment: $conversation_comment
        successful: $conversation_successful
        call: $managed_entity

```

Remove Entity
-------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\RemoveEntity

**Alias:** remove_entity

**Description:** Removes entity with specified class instance.

**Parameters:**
 - target - target that will contain entity instance;

**Configuration Example**
```
- @remove_entity:
    target: $.data #remove the entity being processed
```

Create Related Entity
---------------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\CreateRelatedEntity

**Alias:** create_related_entity

**Description:** Creates workflow related entity with data, persists it to Db and sets it as WorkflowItem entity value.

**Parameters:**
 - data - array of data that should be set to entity.

**Configuration Example**
```
- @create_related_entity:
    conditions:
        # optional condition configuration
    parameters:
        data:
            result: $conversation_result
            comment: $conversation_comment
            successful: $conversation_successful
            call: $managed_entity

OR

- @create_entity:
    data:
        result: $conversation_result
        comment: $conversation_comment
        successful: $conversation_successful
        call: $managed_entity

```

Find Entity
-----------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\RequestEntity

**Alias:** find_entity|request_entity

**Description:** Finds entity by identifier value or "where" condition and saves reference or entity to path.

**Parameters:**
 - class - fully qualified class name of requested entity;
 - attribute - target path where result of action will be saved;
 - identifier - value of identifier of entity to find;
 - where - array of conditions to find entity, key is field name, value is scalar value or path;
 - order_by - array of fields used to sort values, key is field name, value is direction (asc or desc);
 - case_insensitive - boolean flag used to find entity using case insensitive search, default value is false.

**Configuration Example**
```
- @find_entity:
    conditions:
        # optional condition configuration
    parameters:
        class: OroCRM\Bundle\SalesBundle\Entity\LeadStatus
        identifier: 'canceled'
        attribute: $lead.status

OR

- @find_entity:
    class: OroCRM\Bundle\SalesBundle\Entity\LeadStatus
    identifier: 'canceled'
    attribute: $lead.status

OR

- @find_entity:
    class: OroCRM\Bundle\AccountBundle\Entity\Account
    attribute: $account
    where:
        name: $company_name
    order_by:
        date_created: desc
    case_insensitive: true
```

Format Name
-----------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\FormatName

**Alias:** format_name

**Description:** Format entity name based on locale settings.

**Parameters:**
 - attribute - target path where result of action will be saved;
 - object - entity;

**Configuration Example**
```
- @format_name:
    attribute: $.result.formattedCustomerName
    object: $cart.customer
```

Format String
-------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\FormatString

**Alias:** format_string

**Description:** Replace placeholders in string with passed values.

**Parameters:**
 - attribute - target path where result of action will be saved;
 - string - string with placeholders. Placeholder keys must be in format %placeholder_key%;
 - arguments - placeholder values

**Configuration Example**
```
- @format_string:
    attribute: $opportunity_name
    string: '%customer_name% - %shopping_cart_id%'
    arguments:
        customer_name: $.result.formattedCustomerName
        shopping_cart_id: $cart.id
```

Call Method
-----------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\CallMethod

**Alias:** call_method

**Description:** Triggers call of object method with parameters.

**Parameters:**
 - attribute - (optional) target path where result of action will be saved
 - object - path to callee object
 - method - method name of callee object
 - method_parameters - (optional) list of parameters that will be passed to method call


**Configuration Example**
```
- @call_method:
    conditions:
        # optional condition configuration
    parameters:
        attribute: $.leadContactAddAddress
        object: $lead.contact
        method: addAddress
        method_parameters: [$.result.address]

OR

- @call_method: # add Address to Contact
    attribute: $.leadContactAddAddress
    object: $lead.contact
    method: addAddress
    method_parameters: [$.result.address]

```

Create Date
-----------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\CreateDate

**Alias:** create_date

**Description:** Create DateTime object based on date string

**Parameters:**
 - date - (optional) date as string. Current date by default;
 - attribute - target path where result of action will be saved;

**Configuration Example**
```
- @create_date:
    attribute: $sales_funnel_start_date

OR

- @create_date:
    conditions:
            # optional condition configuration
    parameters:
        attribute: $sales_funnel_start_date
        date: '2014-04-01' # must use quotes because date parameter requires string value
```

Create Date Time
----------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\CreateDate

**Alias:** create_datetime

**Description:** Create DateTime object based on date time string

**Parameters:**
 - time - (optional) date time as string. Current time by default;
 - timezone - (optional) timezone as string. UTC timezone by default;
 - attribute - target path where result of action will be saved;

**Configuration Example**
```
- @create_datetime:
    attribute: $sales_funnel_start_datetime

OR

- @create_datetime:
    conditions:
            # optional condition configuration
    parameters:
        attribute: $sales_funnel_start_date
        time: '2014-04-01 12:12:00' # must use quotes because time parameter requires string value
        timezone: Europe/Kiev
```

Start Workflow
--------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\StartWorkflow

**Alias:** start_workflow

**Description:** Triggers start of workflow with configured data. As a result a new WorkflowItem will be produced.

**Parameters:**
 - name - name of Workflow to start
 - attribute - path where result WorkflowItem will be saved
 - entity - path to entity that plays role of managed entity in started Workflow (optional)
 - transition - name of start transition (optional)

**Configuration Example**
```
- @start_workflow: # start workflow and create workflow item
    conditions:
        # optional condition configuration
    parameters:
        name: sales
        attribute: $.result.workflowItem
        entity: $.result.opportunity
        transition: develop

OR

- @start_workflow: # start workflow and create workflow item
    name: sales
    attribute: $.result.workflowItem
    entity: $.result.opportunity
    transition: develop
```


Transit Workflow
--------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\TransitWorkflow

**Alias:** transit_workflow

**Description:** Performs transition for workflow on a specific entity. Workflow must be already started. 

**Parameters:**
 - entity (or first parameter) - path to entity used for transition operation
 - transition (or second parameter) - name of the transition
 - data (or third parameter) - additional data passed to workflow item before transition (optional)
 
**Configuration Example**
```
- @transit_workflow:
    entity: $opportunity
    transition: develop
    data:
        budget_amount: 1000
        probability: 0.95
        
OR

- @transit_workflow:
    conditions:
        # optional condition configuration
    parameters:
        entity: $opportunity
        transition: develop
        data:
            budget_amount: 1000
            probability: 0.95
        
OR

- @transit_workflow:
    [$opportunity, 'develop', { budget_amount: 1000, probability: 0.95 }]
```


Redirect
--------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\Redirect

**Alias:** redirect

**Description:** Redirects unset to some route

**Parameters:**
 - url - URL where user should be redirected
 - route - name of the route, if set than url parameter will be ignored
 - route_parameters - parameters of route

**Configuration Example**
```
- @redirect:
    parameters:
        url: http://google.com

OR

- @redirect:
    url: http://google.com

OR

- @redirect:
    parameters:
        route: some_route_name
        route_parameters: {id: $some_entity.id}
```

Tree Executor
-------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\TreeExecutor

**Alias:** tree

**Description:** Composite object contains a list of actions that will be executed sequentially.

**Configuration Example**
```
- @tree:
    conditions:
        # optional condition configuration
    actions:
        - @create_entity:
            # action configuration here
        - @tree:
            # action configuration here
        # other action

OR

- @tree:
    - @create_entity:
        # action configuration here
    - @tree:
        # action configuration here
    # other action

```

Foreach
-------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\Traverse

**Alias:** traverse|foreach

**Description:** Provides support of iteration over traversable entities (arrays, collections etc).

**Configuration Example**
```
- @foreach:
    array: $order.relatedCalls
    value: $.result.value
    actions:
        - @assign_value: [$.result.value.subject, 'Test Subject']

OR

- @foreach:
    array: $order.relatedCalls
    key: $.result.key
    value: $.result.value
    actions:
        - @assign_value: [$.result.value.subject, $.result.key]

```

Configurable
------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\Configurable

**Alias:** configurable

**Description:** Proxy that requires configuration and builds list of actions
on first invocation of "execute" method. Builds actions tree using action Assembler.
This action is NOT intended to be used in configuration of Workflow,
but it can be used to create actions based on configuration in runtime.

**Parameters:** Receives configuration array as source data.

**Code Example**
```php
$configuration = array(
    array(
        '@create_entity' => array(
            'parameters' => array('class' => 'TestClass', 'attribute' => '$entity'),
        ),
    ),
    array(
        '@assign_value' => array(
            'parameters' => array('$contact.name', 'name'),
        )
    ),
);

/** @var ConfigurableAction $configurableAction */
$configurableAction = $actionFactory->create(Configurable::ALIAS, $configuration);

$configurableAction->execute($context); // build list of actions and execute them
```

Flash Message
-------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\FlashMessage

**Alias:** flash_message

**Parameters:**
 - message - message itself, will be passed to translator. Required.
 - message_parameters - message parameters, that will be passed to translator as second argument. Optional.
 - type - message type applicable for Flash Bag. Optional, info by default.

**Description:** Add flash message to session flash bag. Provides ability to show flash messages on frontend.
Messages are passed through translator.

**Configuration Example**
```
@flash_message:
    message: 'Message %parameter_one%, %parameter_two%'
    type: 'info'
    message_parameters:
        parameter_one: 'test'
        parameter_two: $someEntity.name
```
