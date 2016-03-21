Operations
==========

* [What are Operations?](#what-are-operations)
* [How it works?](#how-it-works)
* [Operation Configuration](#operation-configuration)
* [Configuration Validation](#configuration-validation)
* [Default Operations](#default-operations)
  * [Questions and Answers](#questions-and-answers)

What are Operations?
-----------------

*Operations* provide possibility to assign any interaction with user by specifying them to:
 - Entity classes;
 - Routes;
 - Datagrids.

Every active *operation* will show button (link) on the corresponded page(s). Button will be displayed only if all
described Preconditions are met. The *operation* will be performed by clicking on the button if all described
Preconditions and Conditions are met. Also, after clicking on a button, a modal dialog will be shown with some fields
only if the operation has a config for form dialog.


How it works?
-------------

Each *operation* relates to the some entity types (i.e. consists full class name) or\and routes of pages
where operations should be displayed or\and datagrids. Before page loading, ActionBundle chooses *operations* that have 
corresponded page entity|route. Then these *operations* are checking for their Pre conditions. If all Pre conditions 
are met - Operation's button is displaying.
After a user clicks on the button - all performed operations (and underlined functions) will be executed 
if preconditions of *operation* and conditions of *action groups* are met.

Operation Configuration
-------------

All operations can be described in configuration file ``actions.yml`` under corresponded bundle in `config/oro` 
resource directory.
Look at the example of simple operation configuration that performs some execution logic with entity MyEntity.

```
operations:
    acme_demo_expire_myentity_operation:                            # operation name
        extends: entity_operation_base                              # (optional) parent operation if needed
        replace:                                                    # (optional) the list of nodes that should be replaced in the parent operation
            - frontend_options                                      # node name
        label: aсme.demo.operations.myentity_operation              # label for operation button
        enabled: true                                               # (optional, default = true) is operation enabled
        substitute_operation:  entity_common_operation              # (optional) name of operation that must be substituted with current one if it appears
        entities:                                                   # (optional) list of entity classes
            - Acme\Bundle\DemoBundle\Entity\MyEntity                # entity class
            - AcmeDemoBundle:MyEntity2
        for_all_entities: false                                     # (optional, default = false) is operation match for all entities
        exclude_entities: ['AcmeDemoBundle:MyEntity3']              # (optional) list of entities that must be ignored for this operation (usefull with "for_all_entities" option)
        routes:                                                     # (optional) list of routes
            - acme_demo_myentity_view                               # route name
        datagrids                                                   # (optional) list of datagrids
            - acme-demo-grid                                        # datagrid name
        for_all_datagrids: false                                    # (optional, default = false) is operation available in all datagrids if any   
        groups: ['operations_on_acme_entities']                     # (optional) list of groups that can be assigned to operation (tagging mechanism) to be available or filtered among in usual code or templates
        order: 10                                                   # (optional, default = 0) display order of operation button
        acl_resource: acme_demo_myentity_view                       # (optional) ACL resource name that will be checked while checking that operation execution is allowed

        button_options:                                             # (optional) display options for operation button
            icon: icon-time                                         # (optional) class of button icon
            class: btn                                              # (optional) class of button
            group: aсme.demo.operation.demogroup.label              # (optional) group operation to drop-down on the label
            template: customTemplate.html.twig                      # (optional) custom button template
            page_component_module:                                  # (optional) js-component module
                acme/js/app/components/demo-component
            page_component_options:                                 # (optional) js-component module options
                parameterName: parameterValue
            data:                                                   # custom data attributes which will be added to button
                attributeName: attributeValue

        frontend_options:                                           # (optional) display options for operation button
            template: customDialogTemplate.html.twig                # (optional) custom template, can be used both for page or dialog
            title: Custom Title                                     # (optional) custom title
            options:                                                # (optional) modal dialog options
                allowMaximize: true
                allowMinimize: true
                dblclick: maximize
                maximizedHeightDecreaseBy: minimize-bar
                width: 1000
            show_dialog: true                                       # (optional, by default: true) if `false` - operation will be opened on page
            confirmation: aсme.demo.operation_perform_confirm       # (optional) Confirmation message before start operation`s execution

        attributes:                                                 # (optional) list of all existing attributes
            demo_attr:                                              # attribute name
                label: Demo Field                                   # attribute label
                type: string                                        # attribute type
                property_path: data.demo                            # (optional if label and type are set) path to entity property, which helps to automatically defined attribute metadata
                options:                                            # attribute options
                    class: \Acme\Bundle\DemoBundle\Model\MyModel    # (optional) entity class name, set if type is entity

        datagrid_options:
            mass_action_provider:                                   # (optional) service name, marked with "oro_action.datagrid.mass_action_provider" tag
                acme.action.datagrid.mass_action_provider           # and must implement Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface
            mass_action:                                            # (optional) configuration of datagrid mass action
                type: window
                label: acme.demo.mass_action.label
                icon: plus
                route: acme_demo_bundle_massaction
                frontend_options:
                    title: acme.demo.mass_action.action.label
                    dialogOptions:
                        modal: true
                        ...

        form_options:                                               # (optional) parameters which will be passed to form dialog
            attribute_fields:                                       # list of attribute fields which will be shown in dialog
                demo_attr:                                          # attribute name (must be configured in `attributes` block of action config)
                    form_type: text                                 # needed type of current field
                        options:                                    # list of form field options
                            required: true                          # define this field as required
                            constraints:                            # list of constraints
                                - NotBlank: ~                       # this field must be filled
            attribute_default_values:                               # (optional) define default values for attributes
                demo_attr: $demo                                    # use attribute name and property path or simple string for attribute value

        form_init:                                                  # (optional) any needed functions which will execute before showing form dialog
            - @assign_value:                                        # function alias
                conditions:                                         # (optional) conditions list to allow current function
                    @empty: $description                            # condition definition
                parameters: [$.demo_attr, 'Demo Data']              # parameters of current function

        preactions:                                                 # (optional) any needed pre functions which will execute before pre conditions
            - @create_datetime:                                     # function alias
                attribute: $.date                                   # function parameters

        preconditions:                                              # (optional) pre conditions for display Action button
            @gt: [$updatedAt, $.date]                               # condition definition

        actions:                                                    # (optional) any needed functions which will execute after click on th button
            - @assign_value: [$expired, true]                        # function definition
```

 This configuration describes operation that relates to the ``MyEntity`` entity. On the View page (acme_demo_myentity_view)
of this entity (in case of field 'updatedAt' > new DateTime('now')) will be displayed button with label
"adme.demo.myentity.operations.myentity_operation". After click on this button - will run postfunction "assign_value"
and set field 'expired' to `true`.
 If `form_options` are specified after click on button will be shown form dialog with attributes fields. And functions
will run only on form submit.

Configuration Validation
------------------------

To validate all operations configuration execute a command:

```
php app/console oro:action:configuration:validate
```

**Note:** All configurations apply automatically after their changes on dev environment.


Default Operations
---------------

**Oro Action Bundle** defines several system wide default operations for common purpose. Those are basic CRUD-called
operations for entities:
 
 - `UPDATE` - operation to edit entity, using route from `routeUpdate` option of entity configuration.
 - `DELETE` - operation for entity deletion, using route from `routeDelete` option of entity configuration

  If Default Operations are suppose to be used in not default application - e.g. `commerce` - routes will be retrieved
from `routeCommerceUpdate` and `routeCommerceDelete` options.

  Configs for default operations placed in `Resources/config/action.yml` file under **Oro Action Bundle** directory.

### Questions and Answers

**How I can disable CRUD default operation for my Bundle?**

  Suppose you need to disable default `DELETE` operation for your new entity `MyEntity`.
Here the case which describe the way. You can do this in `actions.yml` under your bundle config resources directory:

```
operations:
    DELETE:
        exclude_entities: ['MyEntity']
```
  This will merge addition special condition to default operation during config compilation. 
So that default operation `DELETE` will not be matched for your entity and will not be displayed as well.

**How I can modify CRUD default operation for my Bundle?**
  If you need to customize somehow a default (or any other) operation. Suppose to change basically its `label`, you can
do thing like that:
 
```
operations:
    my_special_entity_custom_edit:
        extends: UPDATE                         # this is for keeping all other properties same as in default
        label: 'Modify me'                      # custom label
        substitute_operation: UPDATE            # replace UPDATE operation with current one
        entities: ['MyEntity']                  # replacement will occur only if this operation will be matched by entity
        for_all_entities: false                 # overriding extended property for `entities` field matching only
```
  Here is custom modification through substitution mechanism. When operation mentioned in `substitute_operation` field 
will be replaced by current one.
  Additionally there present limitation in field `entities` that will pick this custom action for substitution of default
one only when context will be matched by that entity.
For those who need to make full replacement of operation instead of extended copy of it - `extends` field can be omitted
and own, totally custom, body defined.

  See [substitution](./configuration-reference.md#substitution-of-operations) section in
config documentation](./configuration-reference.md) for more details.