Functions
=========

Table of Contents
-----------------
 - [Run Action](#run-action)
 - [Format Name](#format-name)
 - [Create Date](#create-date)

Run Action
----------

**Class:** Oro\Bundle\ActionBundle\Action\RunAction

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


Format Name
-----------

**Class:** Oro\Bundle\ActionBundle\Action\FormatName

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


Create Date
-----------

**Class:** Oro\Bundle\ActionBundle\Action\CreateDate

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
