Configuration Reference
=======================

Table of Contents
-----------------
 - [Overview](#overview)
 - [Configuration File](#configuration-file)
 - [Configuration Loading](#configuration-loading)
 - [Defining a Workflow](#defining-a-workflow)
   - [Example](#example)
 - [Attributes Configuration](#attributes-configuration)
   - [Example](#example-1)
 - [Steps configuration](#steps-configuration)
   - [Example](#example-2)
 - [Transitions Configuration](#transitions-configuration)
   - [Example](#example-3)
 - [Transition Triggers Configuration](#transition-triggers-configuration)
 - [Transition Definition Configuration](#transition-definition-configuration)
   - [Example](#example-4)
 - [Conditions Configuration](#conditions-configuration)
   - [Example](#example-5)
 - [Post Actions](#post-actions)
   - [Example](#example-6)
 - [Example Workflow Configuration](#example-workflow-configuration)
   - [Configuration](#configuration)
   - [PhoneCall Entity](#phonecall-entity)
   - [PhoneConversation Entity](#phoneconversation-entity)
   - [Flow Diagram](#flow-diagram)

Overview
========

Configuration of Workflow declares all aspects related to specific workflow:

* basic properties of workflow like name and entity
* steps and transitions
* attributes involved in workflow
* entity that is related to workflow

Structure of configuration is declared in class Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration.

Configuration File
==================

Configuration must be placed in a file named Resources/config/oro/workflows.yml. For example
src/Acme/DemoWorkflowBundle/Resources/config/oro/workflows.yml.

Configuration file may be split by parts. All included parts must be placed under imports section. Imports may be used
in any part of workflow configuration.

**Example - workflows.yml**
```
imports:
    - { resource: 'workflows/b2b_flow_lead.yml' }
    - { resource: 'workflows/b2b_flow_sales.yml' }
    - { resource: 'workflows/b2b_flow_sales_funnel.yml' }
```

**Example - b2b_flow_lead.yml**
```
imports:
    - { resource: 'b2b_flow_lead/steps.yml' }
    - { resource: 'b2b_flow_lead/attributes.yml' }
    - { resource: 'b2b_flow_lead/transitions.yml' }
    - { resource: 'b2b_flow_lead/transition_definitions.yml' }

workflows:
    b2b_flow_lead:
        entity: Oro\Bundle\SalesBundle\Entity\Lead
        entity_attribute: lead
        start_step: new
```

Configuration Loading
=====================

To load configuration execute a command:

```
php app/console oro:workflow:definitions:load
```
Command has two options: "directories" that allows user to specify which directories will be used to find definitions,
and "workflows" that define names of definitions required to load.

**Important**

Workflow configuration cannot be merged, it means that you cannot override workflow that is defined in other bundle.
If you will declare a workflow and another bundle will declare it's own workflow with the same name the command will
trigger exception and data won't be saved.

Translations File
=================
Together with workflows configurations, for almost each sections that specified below there should be defined
 translation text under corresponded key to display correct UI text.2
Configuration of translations are implemented in the same way as other translation resources (you might know them by
files placed under `<YourBundle>/Resources/translation/messages.en.yml` or
`<YourBundle>/Resources/translations/jsmessages.en.yml`.
For workflows there should be created their's own translations file
`<YourBundle>Resources/translations/workflows.{lang_code}.yml`
Where `{lang_code}` is your preferable language code for translations that gathered there.
Further in each section that describe workflow configuration part would be note provided with a proper
**Translatable** type for translatable fields. That field describe value that can be defined only 
in workflows.{lang_code}.yml file but never in configuration.

Defining a Workflow
===================

Root element of configuration is "workflows". Under this element workflows can be defined.

Single workflow configuration has next properties:

* **name**
    *string*
    Workflow should have a unique name in scope of all application. As workflow configuration doesn't support merging
    two workflows with the same name will lead to exception during configuration loading.
* **label**
    *Translatable*: `oro.workflow.{workflow_name}.label` 
    This value will be shown in the UI
* **entity**
    *string*
    Class name of workflow related entity. **Important:** Entity either must be extended or custom
    or it must have fields to contain workflow item and step.
* **entity_attribute**
    *string*
    Name of the attribute used to store related entity
* **is_system**
    *boolean*
    Flag that define whether this definition is system. System definition can't be edited or removed.
    All definitions loaded from *.yml files automatically marked as system.
* **start_step**
    *string*
    The name of start step. If Workflow has start transition then start_step is optional, otherwise it's required.
* **steps_display_ordered**
    *boolean*
    If this flag is true, then workflow step widget will show all steps according to their order (including not passed)
    on entity view page, otherwise widget will show only passed steps.
* **attributes**
    Contains configuration for Attributes
* **steps**
    Contains configuration for Steps
* **transitions**
    Contains configuration for Transitions
* **transition_definitions**
    Contains configuration for Transition Definitions
* **priority** - integer value of current workflow dominance level in part of automatically performed tasks (ordering, exclusiveness). It is recommended to use high degree integer values to give a scope for 3rd party integrators.
* **exclusive_active_groups** - list of group names for which current workflow should be active exclusively
* **exclusive_record_groups** - list of group names for which current workflow can not be performed together with other workflows with one of specified groups. E.g., no concurrent transitions are possible among workflows in same exclusive_record_group. 
* **entity_restrictions**
    Contains configuration for Workflow Restrictions
* **defaults** - node for default workflow configuration values that can be changed in UI later. 
    * **active** - determine if workflow should be active right after first load of configuration.



Example
-------
```
workflows:                                                    # Root elements
    b2b_flow_sales:                                           # A unique name of workflow
        defaults:
            active: true                                      # Active by default (when config is loaded)
        entity: Oro\Bundle\SalesBundle\Entity\Opportunity  # Workflow will be used for this entity
        entity_attribute: opportunity                         # Attribute name used to store root entity
        is_system: true                                       # Workflow is system, i.e. not editable and not deletable
        start_step: qualify                                   # Name of start step
        steps_display_ordered: true                           # Show all steps in step widget
        priority: 100                                         # Priority level
        exclusive_active_groups: [b2b_sales]                  # Only one active workflow from 'b2b_sales' group can be active
        exclusive_record_groups:
            - sales                                           # Only one workflow from group 'sales' can be started at time for the entity
        attributes:                                           # configuration for Attributes
                                                              # ...
        steps:                                                # configuration for Steps
                                                              # ...
        transitions:                                          # configuration for Transitions
                                                              # ...
        transition_definitions:                               # configuration for Transition Definitions
                                                              # ...
        entity_restrictions:                                  # configuration for Restrictions
                                                              # ...
```

Attributes Configuration
========================

Workflow define configuration of attributes. When Workflow Item is created it can manipulate it's own data
(Workflow Data) that is mapped by Attributes. Each attribute must to have a type and may have options.
When Workflow Item is saved it's data is serialized according to configuration of attributes. Saving data that is
not mapped by any attribute or mismatched with attribute type is restricted.

Single attribute can be described with next configuration:

* **unique name**
    Workflow attributes should have unique name in scope of Workflow that they belong to.
    Step configuration references attributes by this value.
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
        elements of array should be scalars or objects that supports serialize/deserialize
    * **object**
        object should support serialize/deserialize, option "class" is required for this type
    * **entity**
        Doctrine entity, option "class" is required and it must be a Doctrine manageable class
* **label**
    *translatable*: `oro.workflow.{workflow_name}.attribute.{attribute_name}.label`
    Label can be shown in the UI
* **entity_acl**
    Defines an ACL for the specific entity stored in this attribute.
    * **update**
        *boolean*
        Can entity be updated. Default value is true.
    * **delete**
        *boolean*
        Can entity be deleted. Default value is true.
* **property_path**
    *string*
    Used to work with attribute value by reference and specifies path to data storage. If property path is specified
    then all other attribute properties except name are optional - they can be automatically guessed
    based on last element (field) of property path.
* **options**
    Options of an attribute. Currently next options are supported
    * **class**
        *string*
        Fully qualified class name. Allowed only when type either entity or object.
    * **multiple**
        *boolean*
        Indicates whether several entities are supported. Allowed only when type is entity.

**Notice**
Attribute configuration does not contain any information about how to render attribute on step forms,
it's responsibility of "Steps configuration". This make possible to render one attribute in different ways on steps.
Browse class *Oro\Bundle\WorkflowBundle\Model\AttributeAssembler* for more details.

Example
-------

```
workflows:
    b2b_flow_sales:
        # ...
        new_account:
            type: entity
            entity_acl:
                delete: false
            options:
                class: Oro\Bundle\AccountBundle\Entity\Account
        new_company_name:
            type: string
        opportunity:
            property_path: sales_funnel.opportunity
        opportunity_name:
            property_path: sales_funnel.opportunity.name
```

Steps configuration
===================

Steps are like nodes in graph of Workflow Transitions. Step must have a unique name and can optionally
contain form options, allowed transitions and other options. If Workflow has type wizard user will be able to see in
what step Workflow instance is at the moment, possible transitions and form of current step (if it is configured
via form options). Step can be connected with attributes via form options. On different step it is possible to attach
any attribute with any form options.

Summarizing all above, step has next configuration:

* **name**
    *string*
    Step must have unique name in scope of Workflow
* **label**
    *Translatable*: `oro.workflow.{workflow_name}.step.{step_name}.label`
    Label of step, can be shown in UI if Workflow has type wizard
* **order**
    *integer*
    This value is used in wizard page to sort steps in UI.
* **is_final**
    *boolean*
    If true than step will be counted as workflow final step.
* **entity_acl**
    Defines an ACL for an entity related to the specified attribute when workflow is in this step.
    * **update**
        *boolean*
        Can entity be updated. Default value is true.
    * **delete**
        *boolean*
        Can entity be deleted. Default value is true.
* **allowed_transitions**
    Optional list of allowed transitions. If no transitions are allowed it's same as is_final option set to true

Example
-------

```
workflows:
    phone_call:
        # ...
        steps:
            start_call:
                allowed_transitions: # list of allowed transitions from this step
                    - connected
                    - not_answered
                entity_acl:
                    owner:
                        update: false
                        delete: false
            start_conversation:
                allowed_transitions:
                    - end_conversation
            end_call:
                is_final: true
```

Transitions Configuration
=========================

Transitions changes current step of Workflow Item when it's performed. It also uses Transition Definition to check if
it's allowed and to perform Post Actions.

Transition configuration has next options:

* **unique name**
    *string*
    A transition must have unique name in scope of Workflow. Step configuration references transitions by this value.
* **label**
    *Translatable*: `oro.workflow.{workflow_name}.transition.{transition_name}.label` 
    Label of transition, will to be shown in UI.
* **step_to**
    *string*
    Next step name. This is a reference to step that will be set to Workflow Item after transition is performed.
* **transition_definition**
    A references to Transition Definition configuration
* **is_start**
    *boolean*
    If true than this transition can be used to start new workflow. At least one start transition is required if
    workflow doesn't have start_step attribute.
* **is_hidden**
    *boolean*
    Indicates that this transition must be hidden at frontend.
* **is_unavailable_hidden**
    *boolean*
    Indicates that this transition must be hidden at frontend in case when transition is not allowed.
* **acl_resource**
    *string*
    ACL resource name that will be checked while checking that transition execution is allowed
* **acl_message**
    *string*
    Message, that will be sown in case when acl_resource is not granted.
* **message**
    *Translatable*: `oro.workflow.{workflow_name}.transition.{transition_name}.warning_message`
    Notification message, that will be shown at frontend before transition execution.
* **display_type**
    *string*
    Frontend transition form display type. Possible options are: dialog, page.
    Display type "page" require "form_options" to be set.
* **display_type**
    *string*
    Frontend transition form display type. Possible options are: dialog, page.
    Display type "page" require "form_options" to be set.
* **page_template**
    *string*
    Custom transition template for transition pages. Should be extended from OroWorkflowBundle:Workflow:transitionForm.html.twig.
* **dialog_template**
    *string*
    Custom transition template for transition dialogs. Should be extended from OroWorkflowBundle:Widget:widget/transitionForm.html.twig.
* **frontend_options**
    Can have such frontend options as **class** (a CSS class applied to transition button), **icon**
    (CSS class of icon of transition button).
* **form_options**
    These options will be passed to form type of transition, they can contain options for form types of attributes that
    will be shown when user clicks transition button.
* **transition_definition**
    *string*
    Name of associated transition definition.
* **triggers**
    Contains configuration for Workflow Transition Triggers

Example
-------

```
workflows:
    phone_call:
        # ...
        transitions:
            connected:                                      # Unique name of transition
                step_to: start_conversation                 # The name of next step that will be set to Workflow Item
                                                            # when transition will be performed

                transition_definition: connected_definition # A reference to Transition Definition configuration
                frontend_options:
                    icon: 'icon-ok'                         # add icon to transition button with class "icon-ok"
                    class: 'btn-primary'                    # add css class "btn-primary" to transition button
                form_options:
                    attribute_fields:                       # fields of form that will be shown when transition button is clicked
                        call_timeout:
                            form_type: integer
                            options:
                                required: false
            not_answered:
                step_to: end_call
                transition_definition: not_answered_definition
            end_conversation:
                step_to: end_call
                transition_definition: end_conversation_definition
                triggers:
                    -
                        cron: '* * * * *'
                        filter: "e.someStatus = 'OPEN'"
```

*Note* Attribute `label` option for `attribute_fields` in `form_options` of transition are deprecated now. It was moved to
workflows.{lang_code}.yml file as translatable field and has following key to define its text value:
`oro.workflow.{workflow_name}.transition.{transition_name}.attribute.{attribute_name}.label`

Transition Triggers Configuration
=================================

Transition Triggers are used to perform Transition by Event or by cron-definition.

Please note that transition can be performed by trigger even if Workflow not started for the entity yet. 

There are 2 types of triggers:

Event trigger:
--------------

Event trigger configuration has next options.

* **entity_class**
    Class of entity that can trigger transition.
* **event**
    Type of the event, can have the following values: `create`, `update`, `delete`.
* **field**
    Only for `update` event - field name that should be updated to handle trigger.
* **queue**
    [boolean, default = true] Handle trigger in queue (if `true`), or in realtime (if `false`) 
* **require**
    String of Symfony Language Expression that should much to handle the trigger. Following aliases are available:
    * `entity` - Entity object, that dispatched event,
    * `mainEntity` - Entity object of triggers' workflow,
    * `wd` - Workflow Definition object,
    * `wi` - Workflow Item object.
* **relation**
    Property path to `mainEntity` relative to `entity` if they are different.
    
Example
-------

```
workflows:
    phone_call:
        # ...
        transitions:
            connected:
                ...
                triggers:
                    -
                        entity_class: Oro\Bundle\SaleBundle\Entity\Quote    # entity class
                        event: update                                       # event type
                        field: status                                       # updated field
                        queued: false                                       # handle trigger not in queue
                        relation: call                                      # relation to Workflow entity
                        require: "entity.status = 'pending'"                # expression language condition
```

Cron trigger:
--------------

Cron trigger configuration has next options.

* **cron**
    Cron definition.
* **queue**
    [boolean, default = true] Handle trigger in queue (if `true`), or in realtime (if `false`) 
* **filter**
    String of Symfony Language Expression that should much to handle the trigger. Following aliases are available:
    * `e` - Entity,
    * `wd` - Workflow Definition,
    * `wi` - Workflow Item,
    * `ws` - Current Workflow Step.
    
Example
-------

```
workflows:
    phone_call:
        # ...
        transitions:
            connected:
                ...
                triggers:
                    -
                        cron: '* * * * *'                                   # cron definition
                        filter: "e.someStatus = 'OPEN'"                     # dql-filter
```

Transition Definition Configuration
===================================

Transition Definition is used by Transition to check Pre Conditions and Conditions, to perform Init Action and Post
Actions.

Transition definition configuration has next options.

* **preactions**
    Configuration of Pre Actions that must be performed before Pre Conditions check.
* **preconditions**
    Configuration of Pre Conditions that must satisfy to allow displaying transition.
* **conditions**
    Configuration of Conditions that must satisfy to allow transition.
* **actions**
    Configuration of Post Actions that must be performed after transit to next step will be performed.
* **form_init**
    Configuration of Form Init Actions that may be performed on workflow item before conditions and actions.
* **pre_conditions**
    Deprecated, use `preconditions` instead.
* **init_actions**
    Deprecated, use `form_init` instead.
* **post_actions**
    Deprecated, use `actions` instead.

Example
-------

```
workflows:
    phone_call:
        # ...
        transition_definitions:
            connected_definition: # Try to make call connected
                # Set timeout value
                preactions:
                    - @assign_value: [$call_timeout, 120]
                # Check that timeout is set
                conditions:
                    @not_blank: [$call_timeout]
                # Set call_successfull = true
                actions:
                    - @assign_value: [$call_successfull, true]
                form_init:
                    - @increment_value: [$call_attempt]
            not_answered_definition: # Callee did not answer
                # Set timeout value
                preactions:
                    - @assign_value: [$call_timeout, 30]
                # Make sure that caller waited at least 60 seconds
                conditions: # call_timeout not empty and >= 60
                    @and:
                        - @not_blank: [$call_timeout]
                        - @ge: [$call_timeout, 60]
                # Set call_successfull = false
                actions:
                    - @assign_value: [$call_successfull, false]
            end_conversation_definition:
                conditions:
                    # Check required properties are set
                    @and:
                        - @not_blank: [$conversation_result]
                        - @not_blank: [$conversation_comment]
                        - @not_blank: [$conversation_successful]
                # Create PhoneConversation and set it's properties
                # Pass data from workflow to conversation
                actions:
                    - @create_entity: # create PhoneConversation
                        class: Acme\Bundle\DemoWorkflowBundle\Entity\PhoneConversation
                        attribute: $conversation
                        data:
                            result: $conversation_result
                            comment: $conversation_comment
                            successful: $conversation_successful
                            call: $phone_call
```

Entity Restrictions Configuration
=================================

Entity Restrictions add validation rules for configured attributes fields. They do not permit to edit these fields on the attributes' edit or create form,
on the attributes' grids via inline editing,  via API or performing an import.
Single entity restriction can be described with next configuration:

* **unique name**
    *string*
    A restriction must have unique name in scope of Workflow.
* **attribute**
    *string*
    This is reference to workflow attribute (attribute must be of type 'entity').
* **field**
    *string*
    Field name of attribute class for which restriction will be applied. 
* **mode**
    *enum*
    Restriction mode. Allowed values for this option are 'full', 'disallow', 'allow'. Default value is 'full'
     - 'full' mode means that field will be completely disabled for editing. This is default value for this option.
     - 'disallow' mode do not permit to fill field with values listed in 'values' option.
     - 'allow' mode do not permit to fill field with values except listed in 'values' option.
* **values**
    *array*
    Optional list of field values which will be used for restriction with 'allow' and 'disallow' modes. 
* **step**
    *string*
    This is reference to workflow step. Restriction will be applied only When workflow is in this step.
    If no step is provided restriction will be applied for attribute creation.

Example
-------

```
workflows:
    opportunity_flow:
        # ...
        entity_restrictions:
            opportunity_status_creation:           # unique restriction name in scope of this Workflow 
                attribute: opportunity             # attribute's reference links to attribute(attribute must be of type 'entity') 
                field: status                      # field name of attribute class
                mode: disallow                     # restriction mode (default is 'full')
                values:                            # disallowed values for this field
                    - 'won'                  
                    - 'lost'
            opportunity_close_reason_creation:
                attribute: opportunity
                field: closeReason
            opportunity_status_open:
                attribute: opportunity
                field: status
                step: open                        # restriction will be applied only When workflow is in this step.
                mode: disallow
                values:
                    - 'won'
                    - 'lost'
            opportunity_close_reason_open:
                attribute: opportunity
                field: closeReason
                step: open
```

Conditions Configuration
========================

Conditions configuration is a part of Transition Definition Configuration. It declares a tree structure of conditions
that are applied on the Workflow Item to check is the Transition could be performed. Single condition configuration
contains alias - a unique name of condition and options.

Optionally each condition can have a constraint message. All messages of not passed conditions will be shown to user
when transition could not be performed.

There are two types of conditions - preconditions and actually transit conditions. Preconditions is used to check
whether transition should be allowed to start, and actual conditions used to check whether transition can be done.
Good example of usage is transition forms: preconditions are restrictions to show button that open transition
form dialog, and actual transitions are used to validate form content after submitting.

Alias of condition starts from "@" symbol and must refer to registered condition. For example "@or" refers to logical
OR condition.

Options can refer to values of Workflow Data using "$" prefix. For example "$call_timeout" refers to value of
"call_timeout" attribute of Workflow Item that is processed in condition.

Also it is possible to refer to any property of Workflow Item using "$." prefix. For example to refer date time when
Workflow Item was created a string "$.created" can be used.

Example
-------

```
workflows:
    phone_call:
        # ...
        transition_definitions:
            # some transition definition
            qualify_call:
                preconditions:
                    @equal: [$status, "in_progress"]
                conditions:
                    # empty($call_timeout) || (($call_timeout >= 60 && $call_timeout < 100) || ($call_timeout > 0 && $call_timeout <= 30))
                    @or:
                        - @blank: [$call_timeout]
                        - @or:
                            - @and:
                                message: Call timeout must be between 60 and 100
                                parameters:
                                    - @greater_or_equal: [$call_timeout, 60]
                                    - @less: [$call_timeout, 100]
                            - @and:
                                message: Call timeout must be between 0 and 30
                                parameters:
                                    - @less_or_equal: [$call_timeout, 30]
                                    - @greater: [$call_timeout, 0]
```

Pre Actions, Post Actions and Init Action
=========================================

Pre Action, Post Actions and Init Action configuration complements Transition Definition configuration.

Pre Actions will be performed BEFORE the Pre Conditions will be qualified.

Post Actions will be performed during transition AFTER conditions will be qualified and current Step of Workflow Item
will be changed to the corresponding one (step_to option) in the Transition.

Init Actions may be performed before transition. One of possible init actions usage scenario is to fill Workflow
Item with default values, which will be used by Transition form if it any exist.

Action configuration consists from alias of Action (which is a unique name of Action) and options
(if such are required).

Similarly to Conditions alias of Action starts from "@" symbol and must refer to registered Action. For example
"@create_entity" refers to Action which creates entity.

Example
-------

```
workflows:
    phone_call:
        # ...
        transition_definitions:
            # some transition definition
                preactions:
                    - @some_action: ~
                form_init:
                    - @assign_value: [$call_attempt, 1]
                actions:
                    - @create_entity: # create an entity PhoneConversation
                        class: Acme\Bundle\DemoWorkflowBundle\Entity\PhoneConversation
                        attribute: $conversation
                        data: # Fill values of freshly created PhoneConversation with data from current WorkflowItem
                            result: $conversation_result
                            comment: $conversation_comment
                            successful: $conversation_successful
                            call: $phone_call
```

Example Workflow Configuration
==============================

In this example configuration of Workflow there are two entities:

* Phone Call
* Phone Conversation

![Workflow Diagram](../../images/configuration-reference_workflow-example-entities.png)

When Workflow Item is created it's connected to Phone Call. On the first step "Start Call" user can go to
"Call Phone Conversation Step" if a callee answered or to "End Phone Call" step if callee didn't answer.
On the step "Call Phone Conversation" User enter Workflow Data and go to "End Phone Call" step via "End conversation"
transition. On this transition a new Entity of Phone Conversation is created and assigned to Phone Call entity.

Configuration
-------------

```
workflows:
    phone_call:
        entity: Acme\Bundle\DemoWorkflowBundle\Entity\PhoneCall
        start_step: start_call
        steps:
            start_call:
                allowed_transitions:
                    - connected
                    - not_answered
            start_conversation:
                allowed_transitions:
                    - end_conversation
            end_call:
                is_final: true
        attributes:
            phone_call:
                type: entity
                options:
                    class: Acme\Bundle\DemoWorkflowBundle\Entity\PhoneCall
            call_timeout:
                type: integer
            call_successfull:
                type: boolean
            conversation_successful:
                type: boolean
            conversation_comment:
                type: string
            conversation_result:
                type: string
            conversation:
                type: entity
                options:
                    class: Acme\Bundle\DemoWorkflowBundle\Entity\PhoneConversation
        transitions:
            connected:
                step_to: start_conversation
                transition_definition: connected_definition
            not_answered:
                step_to: end_call
                transition_definition: not_answered_definition
            end_conversation:
                step_to: end_call
                form_options:
                    attribute_fields:
                        conversation_comment:
                            options:
                transition_definition: end_conversation_definition
        transition_definitions:
            connected_definition: # Try to make call connected
                # Check that timeout is set
                conditions:
                    @not_blank: [$call_timeout]
                # Set call_successfull = true
                actions:
                    - @assign_value:
                        parameters: [$call_successfull, true]
            not_answered_definition: # Callee did not answer
                # Make sure that caller waited at least 60 seconds
                conditions: # call_timeout not empty and >= 60
                    @and:
                        - @not_blank: [$call_timeout]
                        - @ge: [$call_timeout, 60]
                # Set call_successfull = false
                actions:
                    - @assign_value:
                        parameters: [$call_successfull, false]
            end_conversation_definition:
                conditions:
                    # Check required properties are set
                    @and:
                        - @not_blank: [$conversation_result]
                        - @not_blank: [$conversation_comment]
                        - @not_blank: [$conversation_successful]
                # Create PhoneConversation and set it's properties
                # Pass data from workflow to conversation
                actions:
                    - @create_entity: # create PhoneConversation
                        parameters:
                            class: Acme\Bundle\DemoWorkflowBundle\Entity\PhoneConversation
                            attribute: $conversation
                            data:
                                result: $conversation_result
                                comment: $conversation_comment
                                successful: $conversation_successful
                                call: $phone_call
```

Translation file configuration
------------------------------
To define translatable textual representation of the configuration fields we should create following translation file:
`DemoWorkflowBundle\Resources\translations\workflows.en.yml` with following content.

``` yaml
oro:
    workflow:
        phone_call:
            label: 'Demo Call Workflow'
            step:
                start_call:
                    label: 'Start Phone Call'
                start_conversation:
                    label: 'Call Phone Conversation'
                end_call:
                    label: 'End Phone Call'
            attribute:
                phone_call:
                    label: 'Phone Call'
                call_timeout:
                    label: 'Call Timeout'
                call_successfull:
                    label: 'Call Successful'
                conversation_successful:
                    label: 'Conversation Successful'
                conversation_comment:
                    label: 'Conversation Comment'
                conversation_result:
                    label: 'Conversation Result'
                conversation:
                    label: Conversation
            transition:
                connected:
                    label: Connected
                    warning_message: 'Going to connect...'
                not_answered:
                    label: 'Not answered'
                end_conversation:
                    label: 'End conversation'
                    attribute:
                        conversation_comment:
                            label: 'Comment for the call result'

```
As usual for Symfony translations (messages) files, structure of nodes can be grouped by key dots. This code above 
provide full tree just for example.

PhoneCall Entity
----------------
```
<?php

namespace Acme\Bundle\DemoWorkflowBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="acme_demo_workflow_phone_call")
 * @ORM\Entity
 */
class PhoneCall
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="number", type="string", length=255)
     */
    private $number;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="PhoneConversation", mappedBy="call")
     **/
    private $conversations;

    public function __construct()
    {
        $this->conversations = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getConversations()
    {
        return $this->conversations;
    }
}
```

PhoneConversation Entity
------------------------
```
<?php

namespace Acme\Bundle\DemoWorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="acme_demo_workflow_phone_conversation")
 * @ORM\Entity
 */
class PhoneConversation
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="PhoneCall", inversedBy="conversations")
     * @ORM\JoinColumn(name="call_id", referencedColumnName="id")
     */
    private $call;

    /**
     * @ORM\Column(name="result", type="string", length=255, nullable=true)
     */
    private $result;

    /**
     * @ORM\Column(name="comment", type="string", length=255, nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(name="successful", type="boolean", nullable=true)
     */
    private $successful;

    public function getId()
    {
        return $this->id;
    }

    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setSuccessful($successful)
    {
        $this->successful = $successful;
        return $this;
    }

    public function isSuccessful()
    {
        return $this->successful;
    }

    public function setCall($call)
    {
        $this->call = $call;
        return $this;
    }

    public function getCall()
    {
        return $this->call;
    }
}
```

Flow Diagram
------------

![Workflow Diagram](../../images/configuration-reference_workflow-example-diagram.png)
