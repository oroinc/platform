Actions
=========

Table of Contents
-----------------
 - [Run Action Group](#run-action-group-run_action_group)
 - [Format Name](#format-name-format_name)
 - [Create Date](#create-date-create_date)

Run Action Group `@run_action_group`
------------------------

**Class:** Oro\Bundle\ActionBundle\Action\RunActionGroup

**Alias:** run_action_group

**Description:** Runs named (action group)[./action-groups.md] with passed parameters.

**Options:**
 - attribute - attribute where action group execution result value should be set (optional)
 - action_group - action group name
 - parameters_mapping - 
    

**Configuration Example**
```
- @run_action:
    attribute: $.result
    action_group: acme_demo_action_group
    parameters_mapping:
        entity_class: Acme\Bundle\DemoBundle\Entity\User
        entity_id: $.user.id
```

This config will execute configured **action group** with name `acme_demo_action_group` and parameters gathered under `parameters_mapping` section.
 After execution of **action group** actions body, all processed ActionData will be returned and assigned to `$.result` attribute of caller context.
 
 Please note, that all parameters must pass validation and will be accessible under root node of ActionData in action_group execution body. 
 E.g. `$entity_class` and `$entity_id` respectively to their names. See (Action Groups)[./action-groups.md] documentation for more details.

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
