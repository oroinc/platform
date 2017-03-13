Actions
=========

Table of Contents
-----------------
 - [Run Action Group](#run-action-group-run_action_group)
 - [Format Name](#format-name)
 - [Create Date](#create-date)
 - [Resolve Destination Page](#resolve-destination-page)

Run Action Group `@run_action_group`
------------------------------------

**Class:** Oro\Bundle\ActionBundle\Action\RunActionGroup

**Alias:** run_action_group

**Description:** Runs named [action group](./action-groups.md) with passed parameters.

**Options:**
 - result - (optional) property path where where to put action group execution context value
 - results - (optional) mapping result values PropertyPaths from action-group-context to current context keys
 - action_group - action group name
 - parameters_mapping - map of parameters to be passed to action_group context from current one


**Configuration Example**
```
- @run_action_group:
    result: $.result
    results: 
        result_entity_id: $.demo_new_attribute
    action_group: acme_demo_action_group
    parameters_mapping:
        entity_class: Acme\Bundle\DemoBundle\Entity\User
        entity_id: $.user.id
```

 This config will execute configured **action group** with name `acme_demo_action_group` and parameters gathered under
`parameters_mapping` section.
 After execution of **action group** actions configuration body, processed ActionData (e.g. context) will be returned and assigned to `$.result` attribute of caller context. 
 And `$.result_entity_id` will have the value of `$.demo_new_attribute` from Action Group's context.
 
 Please note, that all parameters must pass validation and will be accessible under root node of ActionData in
action_group execution body. 
 E.g. `$.entity_class` and `$.entity_id` respectively to their names. See [Action Groups](./action-groups.md)
documentation for more details.

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

#OR

- @create_date:
    conditions:
            # optional condition configuration
    parameters:
        attribute: $sales_funnel_start_date
        date: '2014-04-01' # must use quotes because date parameter requires string value
```


Copy Values
-----------

**Class:** Oro\Component\Action\Action\CopyValues

**Alias:** copy_values

**Description:**  Provide copy of values from context or object to provided attribute. Behave same as PHP `array_merge` function.

```
- @copy_values: [$.to, $.from1, $.from2, {key: 'value'}]
```


Resolve Destination Page
------------------------

**Class:** Oro\Bundle\ActionBundle\Action\ResolveDestinationPage

**Alias:** resolve_destination_page

**Description:** Resolve redirect url by route name from entity configuration. Used route parameters `routeName` or `routeView`.

**Options:**
 - destination / 0 - name of route that should be specified in `@Config` annotation of entity
 - entity / 1 - (optional) property path of original entity (by default equals `entity`)
 - attribute / 2 - (optional) target property path where result of action will be saved (by default equals `redirectUrl`)

**Configuration Example**
```
- @resolve_destination_page: view

#OR

- @resolve_destination_page: ['view', $.entity, $.attribute]

#OR

- @resolve_destination_page:
    name: index
    entity: $.data.entity

#OR

- @resolve_destination_page:
    name: index
    entity: $.entity
    attribute: $.attribute
```
Allowed values for parameter `destination`:
 - for index page (`routeName`) value can be `name`
 - for view page (`routeView`) value can be `view`
