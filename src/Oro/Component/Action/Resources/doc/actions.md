Actions
=======

Table of Contents
-----------------
 - [Assign Value](#assign-value)
 - [Assign Active User](#assign-active-user)
 - [Unset Value](#unset-value)
 - [Create Object](#create-object)
 - [Create Entity](#create-entity)
 - [Remove Entity](#remove-entity)
 - [Find Entity](#find-entity)
 - [Format String](#format-string)
 - [Call Method](#call-method)
 - [Create DateTime](#create-date-time)
 - [Redirect](#redirect)
 - [Tree Executor](#tree-executor)
 - [Foreach](#foreach)
 - [Configurable](#configurable)
 - [Flash Message](#flash-message)
 - [Call Service Method](#call-service-method)
 - [Find Entities](#find-entities)


Assign Value
------------

**Class:** Oro\Component\Action\Action\AssignValue

**Alias:** assign_value

**Description:** Sets value of attribute from source

**Parameters:**
 - attribute / 0 - attribute where value should be set;
 - value / 1 - value that should be set;

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

**Class** Oro\Component\Action\Action\AssignActiveUser

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

**Class:** Oro\Component\Action\Action\UnsetValue

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

**Class:** Oro\Component\Action\Action\CreateObject

**Alias:** create_object

**Description:** Creates object with specified class and data, and sets it as attribute value.

**Parameters:**
 - class - fully qualified class name of object to be created;
 - arguments - (optional) array of object constructor arguments;
 - attribute - attribute that will contain the created object instance;
 - data - (optional) array of data that should be set to object;

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

**Class:** Oro\Component\Action\Action\CreateEntity

**Alias:** create_entity

**Description:** Creates entity with specified class and data, and sets it as attribute value.

**Parameters:**
 - class - fully qualified class name of entity to be created;
 - attribute - attribute that will contain the created entity instance;
 - flush - (optional) when flush in DB should be performed.
           Immediately after entity creation if ``true`` or later if ``false`` (default value: false);
 - data - (optional) array of data that should be set to entity.

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

**Class:** Oro\Component\Action\Action\RemoveEntity

**Alias:** remove_entity

**Description:** Removes entity with specified class instance.

**Parameters:**
 - target - target that will contain entity instance;

**Configuration Example**
```
- @remove_entity:
    target: $.data #remove the entity being processed
```

Find Entity
-----------

**Class:** Oro\Component\Action\Action\RequestEntity

**Alias:** find_entity|request_entity

**Description:** Finds entity by parameter value and saves reference or entity to path. You must define at least one of 3 optional parameters: `identifier`, `where` or `order_by`.

**Parameters:**
 - class - fully qualified class name of requested entity;
 - attribute - target path where result of action will be saved;
 - identifier - (optional) value of identifier of entity to find;
 - where - (optional) array of conditions to find entity, key is field name, value is scalar value or path;
 - order_by - (optional) array of fields used to sort values, key is field name, value is direction (asc or desc);
 - case_insensitive - (optional) boolean flag used to find entity using case insensitive search, default value is false;

**Configuration Example**
```
- @find_entity:
    conditions:
        # optional condition configuration
    parameters:
        class: OroCRM\Bundle\SalesBundle\Entity\OpportunityCloseReason
        identifier: 'won'
        attribute: $close_reason

OR

- @find_entity:
    class: OroCRM\Bundle\SalesBundle\Entity\OpportunityCloseReason
    identifier: 'won'
    attribute: $close_reason

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

Format String
-------------

**Class:** Oro\Component\Action\Action\FormatString

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

**Class:** Oro\Component\Action\Action\CallMethod

**Alias:** call_method

**Description:** Triggers call of object method with parameters.

**Parameters:**
 - attribute - (optional) target path where result of action will be saved;
 - object - fully qualified class name of object to be referenced;
 - method - method name of referenced object to be called;
 - method_parameters - (optional) list of parameters that will be passed to method call;


**Configuration Example**
```
- @call_method:
    conditions:
        # optional condition configuration
    parameters:
        attribute: $.result.addressResult
        object: $lead.contact
        method: addAddress
        method_parameters: [$.result.address]

OR

- @call_method: # add Address to Contact
    attribute: $.result.addressResult
    object: $lead.contact
    method: addAddress
    method_parameters: [$.result.address]

```


Create Date Time
----------------

**Class:** Oro\Component\Action\Action\CreateDateTime

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

Redirect
--------

**Class:** Oro\Component\Action\Action\Redirect

**Alias:** redirect

**Description:** Redirects unset to some route

**Parameters:**
 - url - URL where user should be redirected;
 - route - (optional) name of the route, if set than url parameter will be ignored;
 - route_parameters - (optional) parameters of route;

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

**Class:** Oro\Component\Action\Action\TreeExecutor

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

**Class:** Oro\Component\Action\Action\Traverse

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

**Class:** Oro\Component\Action\Action\Configurable

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

**Class:** Oro\Component\Action\Action\FlashMessage

**Alias:** flash_message

**Description:** Add flash message to session flash bag. Provides ability to show flash messages on frontend.
Messages are passed through translator.

**Parameters:**
 - message - message itself, will be passed to translator;
 - message_parameters - (optional) message parameters, that will be passed to translator as second argument;
 - type - (optional) message type applicable for Flash Bag. Set to info by default;

**Configuration Example**
```
@flash_message:
    message: 'Message %parameter_one%, %parameter_two%'
    type: 'info'
    message_parameters:
        parameter_one: 'test'
        parameter_two: $someEntity.name
```

Call Service Method
-------------------

**Class:** Oro\Component\Action\Action\CallServiceMethod

**Alias:** call_service_method

**Description:** Triggers call method from service with parameters.

**Parameters:**
 - attribute - attribute where method result value should be set (optional)
 - service - service name
 - method - name of method to call
 - method_parameters - list of parameters that will be passed to method call.

**Configuration Example**
```
- @call_service_method:
    conditions:
        # optional condition configuration
    parameters:
        attribute: $.em
        service: doctrine
        method: getManagerForClass
        method_parameters: ['Acme\Bundle\DemoBundle\Entity\User']

OR

- @call_method:
    attribute: $.em
    service: doctrine
    method: getManagerForClass
    method_parameters: ['Acme\Bundle\DemoBundle\Entity\User']
```

Find Entities
-------------

**Class:** Oro\Component\Action\Action\FindEntities

**Alias:** find_entities

**Description:** Returns entities by filter.

**Parameters:**
 - attribute - attribute where method result value should be set
 - class - entity class
 - where - array of SQL-expressions for where-conditions (optional if `order_by` set)
 - query_parameters - list of parameters that will be passed to query (optional).
 - order_by - list of fields with order for sorting results (optional if `where` set).

**Configuration Example**
```
- @find_entities:
    class: Acme\Bundle\DemoBundle\Entity\User
    attribute: $.users
    where:
        and:
            - e.age < :age
            - e.loginCount > :cnt
    query_parameters:
        age: 10
        cnt: 0
```
