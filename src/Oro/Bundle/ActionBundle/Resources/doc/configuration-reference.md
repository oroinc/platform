# Configuration Reference

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
  * [Preconditions and Conditions Configuration](#pre-conditions-and-conditions-configuration)
    * [Example](#example-6)
  * [Preactions, Form Init Actions and Actions Configuration](#pre-actions-form-init-actions-and-actions-configuration)
    * [Example](#example-7)

## Overview

Configuration of Operation declares all aspects related to a specific operation:

* basic properties of operation like name, label, order, acl resource, etc
* entities or routes, or datagrids that are related to the operation
* conditions and actions
* attributes involved in the operation
* frontend configuration
* operation dialog parameters

Structure of configuration is declared in the Oro\Bundle\ActionBundle\Configuration\OperationListConfiguration class.

## Configuration File

Configuration must be placed in a file named Resources/config/oro/actions.yml. For example,Acme/Bundle/DemoBundle/Resources/config/oro/actions.yml.

**Example - actions.yml**
```
operations:
    acme_demo_operation:
        label:  Demo Operation
        entities:
            - Acme\Bundle\DemoBundle\Entity\User
        ...
```

## Configuration Loading

All operations configurations are loaded automatically on Symfony container building process. Configuration collect from all
bundles, validate and merge. Merged configuration is stored in app cache.

Eexecute a command to validate configuration manually:

```
php bin/console oro:action:configuration:validate
```

## Configuration Merging

All configurations are merged in the boot bundles order. There are two steps of the merging process: overriding and extending.

**Overriding**

At this step, the application collects all configurations of all operations with the same name and merge them to one
configuration.
The merging process uses simple rules:
 * if the node value is scalar - the value is replaced
 * if the node value is array - this array is complemented by values from the second configuration

After the first step, the application knows about all operations and has only one configuration for each operation.

**Extending**

An this step, the application collects configurations for all operations which contain `extends`. Then, the main operation configuration, specified in `extends`, is copied and merged with the configuration of the original operation. Merging is processed in the same way using the `overriding` step (rules).

## Configuration Replacing

In the merging process, we can replace any node on any level of our configuration. If the `replace` node exists, and it contains
some nodes on the same level, the value of these nodes are replaced by the values from the _last_ configuration from a queue.

## Defining an Operation

Root element of configuration is "operations". The operations are defined under this element.

Single operation configuration has the following properties:

* **name**
    *string*
    Operation should have a unique name in scope of all application.
    
* **extends**
    *string*
    Operation name which is used by configuration as a basis for the current operation.
    
* **label**
    *string*
    This value is shown in the UI.
    
* **substitute_operation**
    *string*
    Name of the operation that can be replaced (e.g. [substituted](#substitution-of-operation)) by the current one.
    
* **enabled**
    *boolean*
    Flag that defines whether this operation is enabled. Disabled operations are used in applications.
    
* **page_reload**
    *boolean*
    By default, it is set to *true*. A flag that defines whether this operation should reload the page after execution. It applies when redirect url or datagrid is not specified inside the *actions* block.
    
* **entities**
    *array*
    Array of entity class names. Operation button is shown on the view/edit pages of the entities.
    
* **for_all_entities**
    *boolean*
    Boolean flag that determines whether the current operation matches the selected entities if any.
    
* **exclude_entities**
    *array*
    List of entities that should be excluded from matching against current operation.
    
* **routes**
    *array*
    Operation button is shown on the pages where the route is mentioned.
    
* **groups**
    *array*
    Defines an array of group names to use with the current operation and behaves the same way as operations tagging. It is the easiest way to select the required group of operations for custom approaches.
    
* **datagrids**
    *array*
    Operation icon is shown as a datagrid-action in the listed datagrids.
    
* **for_all_datagrids**
    *boolean*
    Flag that determines whether the current operation matches all the selected datagrids if any.
    
* **exclude_datagrids**
    *array*
    Defines a list of datagrid names which should be excluded from matching against the current operation.
    
* **order**
    *integer*
    Parameter that specifies the display order of operations buttons.
    
* **acl_resource**
    *string*
    Operation button is shown only if a user has the expected permissions.
    
* **frontend_options**
    Contains configuration for Frontend Options.
    
* **preactions**
    Contains configuration for actions which are performed before preconditions.
    
* **preconditions**
    Contains configuration for Preconditions.
    
* **attributes**
    Contains configuration for Attributes.
    
* **datagrid_options**
    Contains configuration for Datagrid Options.
    
* **form_options**
    Contains configuration for Transitions.
    
* **form_init**
    Contains configuration for Form Init Actions.
    
* **conditions**
    Contains configuration for Conditions.
    
* **actions**
    Contains configuration for Actions.

### Example

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
        acl_resource: acme_demo_action_view             # ACL resource name that will be checked on preconditions step
        frontend_options:                               # configuration for Frontend Options
                                                        # ...
        preactions:                                     # configuration for Preactions
                                                        # ...
        preconditions:                                  # configuration for Preconditions
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

## Matching and Filter Mechanism

There are config fields responsible for matching and filtering operations that correspond to actual context call (e.g.
request, place in template, etc.)

### Filtering

Filters are currently included in single property `groups`.

### Matching

Matching properties are:

- `for_all_entities` and `for_all_datagrids` as wildcards boolean indicators
- the `entities`, `routes`, `datagrids` elements comparisons
- `exclude_entities` and `exclude_datagrids` as exclusion matchers useful for the `for_all_entities` and `for_all_datagrids` wildcards set to `true`.

How does it work? 

**Filters** discard all non matched operations being applied first before matchers. Then, **matchers**, in turn, collect all operations, among filtered, where comparisons meet though the `OR` statement.

For example, if `datagrid` `OR` `route` are present in operation configuration, and they meet in a context, then the operation is added to the result list.

## Substitution of Operation

The substitution happens when the `substitute_operation` parameter is defined, and it corresponds to another operation name that should be displayed (e.g. matched by context). In other words, the operation that defines substitution is located in the UI instead of the operation that is defined in parameter.

 The same [matching and filter mechanisms](#matching-and-filter-mechanism) are applied for replacement operation (e.g. the operation that has the `substitute_operation` parameter) as for the regular one with one important difference: **if no matching or filtering criteria are specified than that operation will be matched automatically - always**.
Though, the operations that did not make any replacement (in context) are removed from the final result list.

## Button Options Configuration

Button Options enable to change an operation button style, override a button template and add some data attributes.

Button Options configuration has the following options:

* **icon**
    *string*
    CSS class the operation button icon.
    
* **class**
    *string*
    CSS class applied to the operation button.
    
* **group**
    *string*
    Name of operation button menu. The operation button is a part of a dropdown buttons menu with a label (specified group).
    All operations within the same group are shown in a dropdown button html menu.
    
* **template**
    *string*
    This option provides possibility to override the button template.
    Should be extended from `OroActionBundle:Operation:button.html.twig`.
    
* **data**
    *array*
    This option provides possibility to add data-attributes to the button tag or dynamic attributes for datagrid action.
    
* **page_component_module**
    *string*
    Name of js-component module for the operation-button (attribute *data-page-component-module*).
    
* **page_component_options**
    *array*
    List of options of js-component module for the operation-button (attribute *data-page-component-options*).

### Example

```
operations:
    demo_operation:
        # ...
        button_options:
            icon: fa-check
            class: btn
            group: aсme.demo.operations.demogroup.label
            template: OroActionBundle:Operation:button.html.twig
            data:
                param: value
                customTitle: $.customTitle
            page_component_module: acmedemo/js/app/components/demo-component
            page_component_options:
                component_name: '[name$="[component]"]'
                component_additional: '[name$="[additional]"]'
```

## Frontend Options Configuration

Frontend Options enable to override an operation dialog, page template, or title as well as to set widget options.

Frontend Options configuration has the following options:

* **template**
    *string*
    You can set custom operation dialog template.
    Should be extended from `OroActionBundle:Operation:form.html.twig`.
    
* **title**
    *string*
    Custom title of operation dialog window.
    
* **title_parameters**
    *array*
    Parameter for replacing placeholders from the title. The operation data can be used.
    
* **options**
    *array*
    Parameters related to widget component with the following options: *allowMaximize*, *allowMinimize*, *dblclick*,
    *maximizedHeightDecreaseBy*, *width*, etc.
    
* **confirmation**
    *string*
    You can show a confirmation message before starting the operation execution. Translate constant should be available
    for JS - placed in `jsmessages.*.yml`.
    
* **show_dialog**
    *boolean*
    By default, this value is `true`. It means that during the operation execution a modal dialog with a form is displayed if the form parameters are set. Otherwise a separate page (like entity update page) with a form is displayed instead.

### Example

```
operations:
    demo_operation:
        # ...
        frontend_options:
            confirmation: aсme.demo.operations.operation_perform_confirm
            template: OroActionBundle:Operation:form.html.twig
            title: aсme.demo.operations.dialog.title
            title_parameters:
                %%some_param%%: $.paramValue
            options:
                allowMaximize: true
                allowMinimize: true
                dblclick: maximize
                maximizedHeightDecreaseBy: minimize-bar
                width: 500
            show_dialog: true
```

## Attributes Configuration

The operation defines configuration of attributes. The operation can manipulate its own data that is mapped by Attributes. Each attribute should have a type and may have options.

Single attribute can be described with the following configuration:

* **unique name**
    Attributes should have a unique name in scope of Operation that they belong to. Form configuration references
    attributes by this value.
    
* **type**
    *string*
    Type of attribute. The following types are supported:
    * **boolean**
    * **bool**
        *alias for boolean*
    * **integer**
    * **int**
        *alias for integer*
    * **float**
    * **string**
    * **array**
        Elements of array should be scalars or objects that support serialization/deserialization.
    * **object**
        Object should support serialization/deserialization, the "class" option is required for this type.
    * **entity**
        Doctrine entity, the "class" option is required, and it must be a Doctrine manageable class.
        
* **label**
    *string*
    Label can be shown in the UI.
    
* **property_path**
    *string*
    Used to work with attribute value by reference and specifies path to data storage. If property path is specified
    then all other attribute properties except name are optional. They can be automatically determined based on the last
    element (field) of the property path.
    
* **options**
    Options of an attribute. Currently the following options are supported:
    * **class**
        *string*
        Fully qualified class name. Enabled only when typing either entity or object.

**Notice**
Attribute configuration does not contain any information about how to render the attribute on step forms, it's
the responsibility of "Form Options".

### Example


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

## Datagrid Options Configuration

 Datagrid options enable to define options of datagrid mass operation. They provide two ways to set mass operation
configuration: using service which returns array of mas operation configuration or set the inline configuration of mass
operation.

 Single datagrid options can be described with the following configuration:

* **mass_action_provider**
    *string*
    Service name. This service must be marked with the `oro_action.datagrid.mass_action_provider` tag. Also it must
    implement Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface. The "getActions" method of this
    provider must return array of mass action configurations.
    
* **mass_action**
    *array*
    Mass action configuration. See the datagrid documentation.
    
* **data**
    *array*
    This option provides possibility to add static attributes to datagrid action. See the datagrid documentation.

**Notice**
Be noted that only one parameter, either "mass_action_provider" or "mass_action", can be used.

### Example


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
            data:
                type: import
                importProcessor: 'acme_import_processor'
                importJob: 'acme_import_from_csv'
```

## Form Options Configuration

These options are passed to form type of operation. They contain options for form types of attributes that are displayed once a user clicks the operation button.

Single form configuration is described with the following configuration:

* **attribute_fields**
    *array*
    List of attributes with their options. All attributes specified in this configuration must be included in the attribute configuration.
    
* **attribute_default_values**
    *array*
    List of default values for attributes. These values are shown in the operation form once it is loaded. 

### Example


```
operations:
    demo_operation:
        # ...
        form_options:
            attribute_fields:
                demo_attr:
                    form_type: Symfony\Component\Form\Extension\Core\Type\TextType
                        options:
                            required: true
                            constraints:
                                - NotBlank: ~
            attribute_default_values:
                demo_attr: $demo
```

## Preconditions and Conditions Configuration

* **preconditions**
    Configuration of preconditions that must be satisfied to enable the operation button displaying.
    
* **conditions**
    Configuration of Conditions that must be satisfied to enable the operation.

It declares a tree structure of conditions that are applied on the Action Data to check if the Operation could be
performed. Single condition configuration contains alias, a unique name of a condition, and options.

Optionally, each condition can have a constraint message. All messages of not passed conditions are shown to a user
if the operation fails to be performed.

There are two types of conditions: preconditions and actually operation conditions. Preconditions are used to check
whether the operation is enabled to be displayed, and actual conditions are used to check whether the operation can be done.

Alias of condition starts from the `@` symbol and must refer to the registered condition. For example, `@or` refers to the logical
OR condition.

Options can refer to values of the main entity in Action Data using the `$` prefix. For example, `$some_value` refers to the value
of "callsome_value" attribute of the entity that is processed in condition.

Also, it is possible to refer to any property of Action Data using the `$.` prefix. For example, the `$.created` string is used to refer to the date attribute.

### Example


```
operations:
    demo_operation:
        # ...
        preconditions:
            @equal: [$name, 'John Dow']
        conditions:
            @not_empty: [$group]
```

## Preactions, Form Init Actions and Actions Configuration

* **preactions**
    Configuration of preactions that may be performed before preconditions, conditions, form init actions, and actions.
    It can be used to prepare some data in Action Data that will be used in preconditions validation.
    
* **form_init**
    Configuration of Form Init Actions that may be performed in Action Data before conditions and actions.
    One of the possible init operations usage scenario is to fill attributes with default values which will be used in
    operation form if any.
    
* **actions**
    Configuration of actions that must be performed after all previous steps are completed. This is the main operation step
    that must contain operation logic. It will be performed only after conditions are qualified.

Similarly to conditions, the alias of action starts from the `@` symbol and must refer to registered actions. For example, the
`@assign_value` refers to the action which set specified value to attribute in Action Data.

### Example


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
