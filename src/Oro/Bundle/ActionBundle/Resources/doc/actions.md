# Actions

## Table of Contents

 - [Run Action Group](#run-action-group-run_action_group)
 - [Format Name](#format-name)
 - [Create Date](#create-date)
 - [Resolve Destination Page](#resolve-destination-page)
 - [Duplicate](#duplicate)

## Run Action Group `@run_action_group`

**Class:** Oro\Bundle\ActionBundle\Action\RunActionGroup

**Alias:** run_action_group

**Description:** Runs named [action group](./action-groups.md) with passed parameters.

**Options:**

 - result - (optional) property path where the action group execution context value is allocated
 - results - (optional) property path where the results from action-group-context are mapped to current context keys
 - action_group - action group name
 - parameters_mapping - map of parameters which are passed to the action_group context from the current one


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

 This configuration executes configured **action group** with the `acme_demo_action_group` name and parameters displayed under the `parameters_mapping` section.
 After the execution of **action group**, processed ActionData (e.g. context) are returned and assigned to the `$.result` attribute of the caller context. 
 And `$.result_entity_id` acquires the value of `$.demo_new_attribute` from the Action Group's context.
 
 Please note that all parameters must pass validation and be accessible under root node of ActionData in the action_group execution body. 
 E.g. `$.entity_class` and `$.entity_id` respectively to their names. See the [Action Groups](./action-groups.md) documentation for more details.

## Format Name

**Class:** Oro\Bundle\ActionBundle\Action\FormatName

**Alias:** format_name

**Description:** Format entity name based on locale settings.

**Parameters:**

 - attribute - target path where the action results are saved;
 - object - entity;

**Configuration Example**
```
- @format_name:
    attribute: $.result.formattedCustomerName
    object: $cart.customer
```


## Create Date

**Class:** Oro\Bundle\ActionBundle\Action\CreateDate

**Alias:** create_date

**Description:** Create DateTime object based on date string

**Parameters:**

 - date - (optional) date as a string. The current date by default;
 - attribute - target path where the action results are saved.

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


## Copy Values

**Class:** Oro\Component\Action\Action\CopyValues

**Alias:** copy_values

**Description:**  Copies the values from a context or an object to the provided attribute and behaves the same as the `array_merge` PHP function.

```
- @copy_values: [$.to, $.from1, $.from2, {key: 'value'}]
```


## Resolve Destination Page

**Class:** Oro\Bundle\ActionBundle\Action\ResolveDestinationPage

**Alias:** resolve_destination_page

**Description:** Resolves the URL redirection activity by a route name from the entity configuration using the `routeName` or `routeView` parameters.

**Options:**

 - destination / 0 - the route name specified in the `@Config` annotation of an entity
 - entity / 1 - (optional) property path of the original entity (by default, equals to `entity`)
 - attribute / 2 - (optional) target property path where the action results are saved (by default, equals to `redirectUrl`)

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
Allowed values for the `destination` parameter:
 - for the index page (`routeName`) value can be `name`
 - for the view page (`routeView`) value can be `view`

## Duplicate

**Class:** Oro\Bundle\ActionBundle\Action\DuplicateEntity

**Alias:** dupliсate, duplicate_entity

**Description:** Duplicate entity object

**Options:**

- entity - (optional) a property path of the original entity (by default, the `getEntity()` method is used from context)
- target - (optional) a property path of the original entity, alias for `entity`
- settings - (optional) a list of filters and matchers to be applied to
- attribute - a target property path where the action results are saved

**Filters and Matchers:**

Available filters: `setNull`, `keep`, `collection`, `emptyCollection`, `replaceValue`, and `shallowCopy`.
Available matchers: `property`, `propertyName`, and `propertyType`.
For more information please refer to the [DeepCopy](https://packagist.org/packages/myclabs/deep-copy) documentation.

**Configuration Example**
```
- @duplicate:
    target: $.entity
    attribute: $.entityCopy
    settings:
      - [[setNull], [propertyName, [id]]]
      - [[collection], [propertyName, [items]]]
      - [[replaceValue, $.currentUser], [propertyName, [user]]]
      - [[keep], [propertyName, [owner]]]
      - [[shallowCopy], [propertyType, ['\DateTime']]]
      - [[keep], [propertyType, ['%oro_user.entity.user%']]]
```
