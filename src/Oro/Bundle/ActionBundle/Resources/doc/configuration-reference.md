Configuration Reference
=======================
  * [Overview](#overview)
  * [Configuration File](#configuration-file)
  * [Configuration Loading](#configuration-loading)
  * [Configuration Merging](#configuration-merging)
  * [Configuration Replacing](#configuration-replacing)
  * [Defining an Operation](#defining-an-operation)
    * [Example](#example)
  * [Matching and Filter Mechanism](#matching-and-filter-mechanism)
    * [Filtering](#filtering)
    * [Matching](#matching)
  * [Substitution of Operation](#substitution-of-operation)
  * [Button Options Configuration](#button-options-configuration)
    * [Example](#example-1)
  * [Frontend Options Configuration](#frontend-options-configuration)
    * [Example](#example-2)
  * [Attributes Configuration](#attributes-configuration)
    * [Example](#example-3)
  * [Datagrid Options Configuration](#datagrid-options-configuration)
    * [Example](#example-4)
  * [Form Options Configuration](#form-options-configuration)
    * [Example](#example-5)
  * [Pre Conditions and Conditions Configuration](#pre-conditions-and-conditions-configuration)
    * [Example](#example-6)
  * [Pre Actions, Form Init Actions and Actions Configuration](#pre-actions-form-init-actions-and-actions-configuration)
    * [Example](#example-7)

Overview
========

Configuration of Operation declares all aspects related to specific operation:

* basic properties of operation like name, label, order, acl resource, etc
* entities or routes or datagrids that is related to operation
* conditions and actions
* attributes involved in operation
* frontend configuration
* operation dialog parameters

Structure of configuration is declared in class Oro\Bundle\ActionBundle\Configuration\OperationListConfiguration.

Configuration File
==================

Configuration must be placed in a file named Resources/config/actions.yml. For example
Acme/Bundle/DemoBundle/Resources/config/actions.yml.

**Example - actions.yml**
```
operations:
    acme_demo_operation:
        label:  Demo Operation
        entities:
            - Acme\Bundle\DemoBundle\Entity\User
        ...
```

Configuration Loading
=====================

All operations configuration load automatically on Symfony container building process. Configuration collect from all
bundles, validate and merge. Merged configuration stored in app cache.

To validate configuration manually execute a command:

```
php app/console oro:action:configuration:validate
```

Configuration Merging
=====================

All configurations merge in the boot bundles order. There are two steps of merging process: overriding and extending.

**Overriding**

On this step application collects all configurations of all operations with the same name and merge their to one
configuration.
Merging uses simple rules:
 * if node value is scalar - value will be replaced
 * if node value is array - this array will be complemented by values from the second configuration

After first step application knows about all operations and have only one configuration for each operation.

**Extending**
On this step application collects configurations for all operations which contain `extends`. Then main operation
configuration, which specified in `extends`, copied and merged with configuration of original operation. Merging use same
way, which use `overriding` step (rules).

Configuration Replacing
=======================

In merge process we can replace any node on any level of our configuration. If node `replace` is exist and it contains
some nodes which located on the same level of node `replace` - value of these nodes will be replaced by values from
_last_ configuration from queue.

Defining an Operation
=====================

Root element of configuration is "operations". Under this element operations can be defined.

Single operation configuration has next properties:

* **name**
    *string*
    Operation should have a unique name in scope of all application.
* **extends**
    *string*
    Operation name, which configuration will be used as basis for current operation.
* **label**
    *string*
    This value will be shown in the UI.
* **substitute_operation**
    *string*
    Name of operation that can be replaced (e.g. [substituted](#substitution-of-operation)) by current one.
* **enabled**
    *boolean*
    Flag that define whether this operation is enabled. Disabled operations will not be used in application.
* **entities**
    *array*
    Array of entity class names. Operation button will be shown on view/edit pages of this entities.
* **for_all_entities**
    *boolean*
    Boolean flag that determines that current operation matched against all entities if any present.
* **exclude_entities**
    *array*
    List of entities that should be excluded from matching against current operation
* **routes**
    *array*
    Operation button will be shown on pages which route is in list.
* **groups**
    *array*
    Define an array of group names to use with current operation. Behave like tagging of operations. Easiest way to pick needed group of operations for custom approaches.
* **datagrids**
    *array*
    Operation icon will be shown as an datagrid-action in listed datagrids.
* **for_all_datagrids**
    *boolean*
    Flag that determines that current operation should be matched for all datagrids if any present.
* **exclude_datagrids**
    *array*
    Define a list of datagrid names witch should be excluded from matching with current operation.
* **order**
    *integer*
    Parameter that specifies the display order of operations buttons.
* **acl_resource**
    *string*
    Operation button will be shown only if user have expected permissions.
* **frontend_options**
    Contains configuration for Frontend Options
* **preactions**
    Contains configuration for actions which will be performed before preconditions
* **preconditions**
    Contains configuration for Pre Conditions
* **attributes**
    Contains configuration for Attributes
* **datagrid_options**
    Contains configuration for Datagrid Options
* **form_options**
    Contains configuration for Transitions
* **form_init**
    Contains configuration for Form Init Actions
* **conditions**
    Contains configuration for Conditions
* **actions**
    Contains configuration for Actions

Example
-------
```
operations:                                             # root elements
    demo_operation:                                     # name of operation
        extends: demo_operation_base                    # base operation name
        label: acme.demo.operations.myentity_operation  # this value will be shown in UI for operation button
        substitute_operation: some_operation            # configuration of 'some_operation' will be replaced by configuration of this operation
        enabled: false                                  # operation is disabled, means not used in application
        entities:                                       # on view/edit pages of this entities operation button will be shown
            - Acme\Bundle\DemoBundle\Entity\MyEntity    # entity class name
        routes:                                         # on pages with these routes operation button will be shown
            - acme_demo_action_view                     # route name
        datagrids                                       # in listed datagrids operation icon will be shown
            - acme-demo-grid                            # datagrid name
        order: 10                                       # display order of operation button
        acl_resource: acme_demo_action_view             # ACL resource name that will be checked on pre conditions step
        frontend_options:                               # configuration for Frontend Options
                                                        # ...
        preactions:                                     # configuration for Pre Actions
                                                        # ...
        preconditions:                                  # configuration for Pre Conditions
                                                        # ...
        attributes:                                     # configuration for Attributes
                                                        # ...
        datagrid_options:                               # configuration for Datagrid Options
                                                        # ...
        form_options:                                   # configuration for Form Options
                                                        # ...
        form_init:                                      # configuration for Form Init Actions
                                                        # ...
        conditions:                                     # configuration for Conditions
                                                        # ...
        actions:                                        # configuration for Actions
                                                        # ...
```

Matching and Filter Mechanism
=============================
There are config fields responsible for matching and filtering operations that corresponds to actual context call (e.g.
request, place in template, etc.)
Filtering
---------
Filters are presents in single property `groups` for now

Matching
--------
Matching properties are:
- `for_all_entities` and `for_all_datagrids` as wildcards boolean indicators.
- And elements comparisons: `entities`, `routes`, `datagrids`
- also here is present `exclude_entities` and `exclude_datagrids` - as exclusion matchers useful with wildcard 
`for_all_entities` and `for_all_datagrids` defined to `true`.

How it works? **Filters** discards all non matched operations and applied first before matchers.
Then, **matchers**, in turn, collect all operations, among filtered, where any of comparison met though `OR` statement.
E.g.
 if `datagrid` `OR` `route` will be met in context and present in operation config then that operation will be added to 
result list.

Substitution of Operation
=========================

When parameter `substitute_operation` is defined and it corresponds to other operation name that should be displayed
(e.g. matched by context) substitution happens. In other words, operation that define substitution will be positioned in
UI instead of operation that defined in parameter.
For replacement operation (e.g. operation that have `substitute_operation` parameter) the same
[matching and filter mechanisms](#matching-and-filter-mechanism) are applied as for normal operation
with one important difference: **if no matching or filtering criteria are specified than that operation will be matched
automatically - always**.
But after all - operations that did not make any replacement (in context) will be cleared from final result list.

Button Options Configuration
============================

Button Options allow to change operation button style, override button template and add some data attributes.

Button Options configuration has next options:

* **icon**
    *string*
    CSS class of icon of operation button
* **class**
    *string*
    CSS class applied to operation button
* **group**
    *string*
    Name of operation button menu. Operation button will be part of dropdown buttons menu with label (specified group).
    All operations with same group will be shown in one dropdown button html menu.
* **template**
    *string*
    This option provide possibility to override button template.
    Should be extended from `OroActionBundle:Operation:button.html.twig`
* **data**
    *array*
    This option provide possibility to add data-attributes to the button tag.
* **page_component_module**
    *string*
    Name of js-component module for the operation-button  (attribute *data-page-component-module*).
* **page_component_options**
    *array*
    List of options of js-component module for the operation-button (attribute *data-page-component-options*).

Example
-------
```
operations:
    demo_operation:
        # ...
        button_options:
            icon: icon-ok
            class: btn
            group: aсme.demo.operations.demogroup.label
            template: OroActionBundle:Operation:button.html.twig
            data:
                param: value
            page_component_module: acmedemo/js/app/components/demo-component
            page_component_options:
                component_name: '[name$="[component]"]'
                component_additional: '[name$="[additional]"]'
```

Frontend Options Configuration
==============================

Frontend Options allow to override operation dialog or page template, title and set widget options.

Frontend Options configuration has next options:

* **template**
    *string*
    You can set custom operation dialog template.
    Should be extended from `OroActionBundle:Operation:form.html.twig`
* **title**
    *string*
    Custom title of operation dialog window.
* **options**
    *array*
    Parameters related to widget component. Can be specified next options: *allowMaximize*, *allowMinimize*, *dblclick*,
    *maximizedHeightDecreaseBy*, *width*, etc.
* **confirmation**
    *string*
    You can show confirmation message before start operation`s execution. Translate constant should be available
    for JS - placed in jsmessages.*.yml
* **show_dialog**
    *boolean*
    By default this value is `true`. It mean that on operation execution, if form parameters are set, will be shown
    modal dialog with form. Otherwise will be shown separate page (like entity update page) with form.

Example
-------
```
operations:
    demo_operation:
        # ...
        frontend_options:
            confirmation: aсme.demo.operations.operation_perform_confirm
            template: OroActionBundle:Operation:form.html.twig
            title: aсme.demo.operations.dialog.title
            options:
                allowMaximize: true
                allowMinimize: true
                dblclick: maximize
                maximizedHeightDecreaseBy: minimize-bar
                width: 500
            show_dialog: true
```

Attributes Configuration
========================

Operation define configuration of attributes. Operation can manipulate it's own data that is mapped by
Attributes. Each attribute must to have a type and may have options.

Single attribute can be described with next configuration:

* **unique name**
    Attributes should have unique name in scope of Operation that they belong to. Form configuration references
    attributes by this value.
* **type**
    *string*
    Type of attribute. Next types are supported:
    * **boolean**
    * **bool**
        *alias for boolean*
    * **integer**
    * **int**
        *alias for integer*
    * **float**
    * **string**
    * **array**
        Elements of array should be scalars or objects that supports serialize/deserialize
    * **object**
        Object should support serialize/deserialize, option "class" is required for this type
    * **entity**
        Doctrine entity, option "class" is required and it must be a Doctrine manageable class
* **label**
    *string*
    Label can be shown in the UI
* **property_path**
    *string*
    Used to work with attribute value by reference and specifies path to data storage. If property path is specified
    then all other attribute properties except name are optional - they can be automatically guessed based on last
    element (field) of property path.
* **options**
    Options of an attribute. Currently next options are supported
    * **class**
        *string*
        Fully qualified class name. Allowed only when type either entity or object.

**Notice**
Attribute configuration does not contain any information about how to render attribute on step forms, it's
responsibility of "Form Options".

Example
-------

```
operations:
    demo_operation:
        # ...
        attributes:
            user:
                label: 'User'
                type: entity
                options:
                    class: Oro\Bundle\UserBundle\Entity\User
            company_name:
                label: 'Company name'
                type: string
            group_name:
                property_path: user.group.name
```

Datagrid Options Configuration
==============================

 Datagrid options allow to define options of datagrid mass operation. It provide two way to set mass operation
configuration: using service which return array of mas operation configurations or set inline configuration of mass
operation.

 Single datagrid options can be described with next configuration:

* **mass_action_provider**
    *string*
    Service name. This service must be marked with "oro_action.datagrid.mass_action_provider" tag. Also it must
    implements Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface. Method "getActions" of this
    provider must return array of mass action configurations.
* **mass_action**
    *array*
    Mass action configuration. See datagrid documentation.

**Notice**
It must be used only one parameter "mass_action_provider" or "mass_action".

Example
-------

```
operations:
    demo_operation:
        # ...
        datagrid_options:
            mass_action_provider:
                acme.action.datagrid.mass_action_provider
            mass_action:
                type: window
                label: acme.demo.mass_action.label
                icon: plus
                route: acme_demo_bundle_massaction
                frontend_options:
                    title: acme.demo.mass_action.action.label
                    dialogOptions:
                        modal: true
                        ...
```

Form Options Configuration
==========================

These options will be passed to form type of operation, they can contain options for form types of attributes that will be
shown when user clicks operation button.

Single form configuration can be described with next configuration:

* **attribute_fields**
    *array*
    List of attributes with their options. All attributes specified in this configuration must be contains in attribute
    configuration.
* **attribute_default_values**
    *array*
    List of default values for attributes. This values are shown in operation form on form load.

Example
-------

```
operations:
    demo_operation:
        # ...
        form_options:
            attribute_fields:
                demo_attr:
                    form_type: text
                        options:
                            required: true
                            constraints:
                                - NotBlank: ~
            attribute_default_values:
                demo_attr: $demo
```

Pre Conditions and Conditions Configuration
===========================================

* **preconditions**
    Configuration of Pre Conditions that must satisfy to allow showing operation button.
* **conditions**
    Configuration of Conditions that must satisfy to allow operation.

It declares a tree structure of conditions that are applied on the Action Data to check if the Operation could be
performed. Single condition configuration contains alias - a unique name of condition - and options.

Optionally each condition can have a constraint message. All messages of not passed conditions will be shown to user
when operation could not be performed.

There are two types of conditions - preconditions and actually operation conditions. Preconditions are using to check
whether operation should be allowed to show, and actual conditions used to check whether operation can be done.

Alias of condition starts from "@" symbol and must refer to registered condition. For example "@or" refers to logical
OR condition.

Options can refer to values of main entity in Action Data using "$" prefix. For example "$some_value" refers to value
of "callsome_value" attribute of entity that is processed in condition.

Also it is possible to refer to any property of Action Data using "$." prefix. For example to refer date attribute
with date can be used string "$.created".

Example
-------

```
operations:
    demo_operation:
        # ...
        preconditions:
            @equal: [$name, 'John Dow']
        conditions:
            @not_empty: [$group]
```

Pre Actions, Form Init Actions and Actions Configuration
========================================================

* **preactions**
    Configuration of Pre Actions that may be performed before pre conditions, conditions, form init actions and actions.
    It can be used to prepare some data in Action Data that will be used in pre conditions validation.
* **form_init**
    Configuration of Form Init Actions that may be performed on Action Data before conditions and actions.
    One of possible init operations usage scenario is to fill attributes with default values, which will be used in
    operation form if it exist.
* **actions**
    Configuration of Actions that must be performed after all previous steps are performed. This is main operation step
    that must contain operation logic. It will be performed only after conditions will be qualified.

Similarly to Conditions - alias of Action starts from "@" symbol and must refer to registered Actions. For example
"@assign_value" refers to Action which set specified value to attribute in Action Data.

Example
-------

```
operations:
    demo_operation:
        # ...
        preactions:
            - @assign_value: [$name, 'User Name']
        form_init:
            - @assign_value: [$group, 'Group Name']
        actions:
            - @create_entity:
                class: Acme\Bundle\DemoBundle\Entity\User
                attribute: $user
                data:
                    name: $name
                    group: $group
```
