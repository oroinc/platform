Functions
=========

Table of Contents
-----------------
 - [Call Service Method](#call-service-method)
 - [Find Entities](#find-entities)
 - [Run Action](#run-action)

Call Service Method
-------------------

**Class:** Oro\Component\ConfigExpression\Action\CallServiceMethod

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

**Class:** Oro\Component\ConfigExpression\Action\FindEntities

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

Run Action
----------

**Class:** Oro\Component\ConfigExpression\Action\RunAction

**Alias:** run_action

**Description:** Run action.

**Parameters:**
 - attribute - attribute where action result value should be set (optional)
 - action - action name
 - entity_class - class of Entity for ActionData
 - entity_id - id of Entity for ActionData

**Configuration Example**
```
- @run_action:
    attribute: $.result
    action: acme_demo_action
    entity_class: Acme\Bundle\DemoBundle\Entity\User
    entity_id: $.user.id
```
