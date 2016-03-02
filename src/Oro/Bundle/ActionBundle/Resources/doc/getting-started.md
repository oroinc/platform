Getting Started
===============

Table of Contents
-----------------
 - [What are Actions?](#what-are-actions)
 - [Main Entities](#main-entities)
 - [How it works?](#how-it-works)
 - [Configuration](#configuration)
 - [Action Diagram](#action-diagram)
 - [Configuration Validation](#configuration-validation)
 - [Default Actions](#default-actions)

What are Actions?
-----------------

Actions provide possibility to assign any operations to:
 - Entity classes;
 - Routes;
 - Datagrids.

Every active action will show button (link) on the corresponded page(s). Button will be displayed only if all described
Pre conditions are met. Action will be performed after click on the button if all described Pre conditions
and Conditions are met. Also after click on button will be shown modal dialog with some fields only if action has config
for form dialog.

Main Entities
-------------

Action consists of several related entities.

* **Action** - main model that contains information about specific action. It contains the most important
information like action related entity classes (f.e. 'Acme\Bundle\DemoBundle\Entity\MyEntity')
or routes ('acme_demo_myentity_view') or datagrids ('acme-demo-grid'). Action can be enabled or disabled.
Other fields of the action contain information about action name, extended options,
order of display buttons. More options see in [Configuration](#configuration).

* **ActionDefinition** - part of the Action model that contains raw data from action's configuration.

* **Action Data** - container aggregated some data, that will be available on each step of Action. Some of values
associated with some Attribute. Those values can be entered by user directly or assigned via Functions.

* **Attribute** - entity that represent one value in Action, used to render field value on a step form.
Attribute knows about its type (string, object, entity etc.) and additional options.
Attribute contains name and label as additional parameters.

* **Condition** - defines whether specific Action is allowed with specified input data. Conditions can be nested.

* **Functions** - operations are assigned to Action and executed when Action is performed.
There are two kind of actions: Pre Functions, Form Init Functions and Functions.
The difference between them is that Pre Functions are executed before Action button redder, Form Init Functions are
executed before Action and Functions are executed after Action.
Actions can be used to perform any operations with data in Action Data or other entities.

How it works?
-------------

Each action relates to the some entity types (i.e. consists full class name) or\and routes of pages
where action should be displayed or\and datagrids. Before page loading Action Bundle chooses actions that
are corresponded to page's entity\route. Then these actions checking for Pre conditions.
If all Pre conditions are met - Action's button is displaying.
After user click on the button - all functions will be executed if pre conditions and conditions are met.

Configuration
-------------

All actions are described in configuration file ``actions.yml`` corresponded bundle.
Look at the example of simple action configuration that performs some action with entity MyEntity.

```
actions:
    acme_demo_expire_myentity_action:                               # action name
        extends: entity_action_base                                 # (optional) parent action if needed
        replace:                                                    # (optional) the list of nodes that should be replaced in the parent action
            - frontend_options                                      # node name
        label: aсme.demo.actions.myentity_action                    # label for action button
        enabled: true                                               # (optional, default = true) is action enabled
        entities:                                                   # (optional) list of entity classes
            - Acme\Bundle\DemoBundle\Entity\MyEntity                # entity class
            - AcmeDemoBundle:MyEntity2
        routes:                                                     # (optional) list of routes
            - acme_demo_myentity_view                               # route name
        datagrids                                                   # (optional) list of datagrids
            - acme-demo-grid                                        # datagrid name
        order: 10                                                   # (optional, default = 0) display order of action button
        acl_resource: acme_demo_myentity_view                       # (optional) ACL resource name that will be checked while checking that action execution is allowed

        button_options:                                             # (optional) display options for action button
            icon: icon-time                                         # (optional) class of button icon
            class: btn                                              # (optional) class of button
            group: aсme.demo.actions.demogroup.label                # (optional) group action to drop-down on the label
            template: customTemplate.html.twig                      # (optional) custom button template
            page_component_module:                                  # (optional) js-component module
                acme/js/app/components/demo-component
            page_component_options:                                 # (optional) js-component module options
                parameterName: parameterValue
            data:                                                   # custom data attributes which will be added to button
                attributeName: attributeValue

        frontend_options:                                           # (optional) display options for action button
            template: customDialogTemplate.html.twig                # (optional) custom template, can be used both for page or dialog
            title: Custom Title                                     # (optional) custom title
            options:                                                # (optional) modal dialog options
                allowMaximize: true
                allowMinimize: true
                dblclick: maximize
                maximizedHeightDecreaseBy: minimize-bar
                width: 1000
            show_dialog: true                                       # (optional, by default: true) if `false` - action will be opened on page
            confirmation: aсme.demo.action_perform_confirm          # (optional) Confirmation message before start action`s execution

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

        prefunctions:                                               # (optional) any needed pre functions which will execute before pre conditions
            - @create_datetime:                                     # function alias
                attribute: $.date                                   # function parameters

        preconditions:                                              # (optional) pre conditions for display Action button
            @gt: [$updatedAt, $.date]                               # condition definition

        conditions:                                                 # (optional) pre conditions for display Action button
            @gt: [$updatedAt, $.date]                               # condition definition

        functions:                                                  # (optional) any needed functions which will execute after click on th button
            - @assign_value: [$expired, true]                       # function definition
```

This configuration describes action that relates to the ``MyEntity`` entity. On the View page (acme_demo_myentity_view)
of this entity (in case of field 'updatedAt' > new DateTime('now')) will be displayed button with label
"adme.demo.myentity.actions.myentity_action". After click on this button - will run postfunction "assign_value" and set
field 'expired' to `true`.
If `form_options` are specified after click on button will be shown form dialog with attributes fields. And functions
will run only on form submit.

Configuration Validation
------------------------

To validate all actions configuration execute a command:

```
php app/console oro:action:configuration:validate
```

**Note:** All configurations apply automatically after their changes on dev environment.


Default Actions
---------------

**Oro Action Bundle** define several system wide default actions for common purpose. Those are basic CRUD-called actions for entities:
 
 - `UPDATE` - action to edit entity 
 - `DELETE` - action for entity deletion
 
 You can find them in `Resources/config/action.yml` file under **Oro Action Bundle** directory.

### Questions and Answers

**How I can disable CRUD default action for my Bundle?**

Suppose you need to disable default `DELETE` action for your new entity `MyEntity`.
Here the case which describe the way. You can do this in `actions.yml` under your bundle config resources directory:

```
actions:
    DELETE:
        exclude_entities: ['MyEntity']
```
This will merge addition special condition to default action during config compilation. 
So that default action `DELETE` will not be matched for your entity and will not be displayed as well.

**How I can modify CRUD default action for my Bundle?**
If you need to customize somehow a default (or any other) action. Suppose to change basically its `label`, you can do thing like that:
 
```
actions:
    my_special_entity_custom_edit:
        extends: UPDATE                         # this is for keeping all other properties same as in default
        label: 'Modify me'                      # custom label
        substitute_action: UPDATE               # replace UPDATE action with current one
        entities: ['MyEntity']                  # replacement will occur only if this action will be matched by entity
        for_all_entities: false                 # overriding extended property for `entities` field matching only
```
Here is custom modification through substitution mechanism. When action pointed in `substitute_action` field will be replaced by current one.
Additionally there present limitation in field `entities` that will pick this custom action for substitution of default one only when context will be matched by that entity.
For those who need to make full replacement of action instead of extended copy of it - `extends` field can be omitted and own, totally custom, body defined.  

See [substitution](./configuration-reference.md#substitution-of-action) section in [config documentation](./configuration-reference.md) for more details.

