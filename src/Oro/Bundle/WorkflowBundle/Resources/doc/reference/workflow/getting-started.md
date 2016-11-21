Getting Started
===============

Table of Contents
-----------------
 - [What is Workflow?](#what-is-workflow)
 - [Main Entities](#main-entities)
 - [How it works?](#how-it-works)
 - [Workflow Fields](#workflow-fields)
 - [Activation State](#activation-state)
 - [Mutually Exclusive Workflows](#mutually-exclusive-workflows)
 - [Configuration](#configuration)
 - [Console commands](#console-commands)

What is Workflow?
-----------------

Workflow is a complex solution that allows user to perform set of actions with predefined conditions
for specific entity - each next action depends on previous. Also Workflow can be described as some kind of wizard
that helps user to perform complex actions.
Usually Workflow is used to manage some specific type of entity and to create additional entities.

Main Entities
-------------

Workflow consists of several related entities.

* **Step** - entity that shows current status of Workflow. Before rendering each transitions checked
is it allowed for current Workflow Item. Contains name and list of allowed transitions. Entity involved
in workflow has relation to current workflow step.

* **Attribute** - entity that represent one value in Workflow Item, used to render field value on a step form.
Attribute knows about its type (string, object, entity etc.) and additional options.
Attribute contains name.

* **Transition** - action that change current step of Workflow Item (i.e. moves it from one step to another). Transition
is allowed if it's Conditions are satisfied. Before Transition performed Init Actions are executed and after
transition performed - Post Actions are executed. Transition can be used as a start transition - it means that this
transition will start Workflow and create new instance of Workflow Item. Transition optionally could have a form. In
this case this form will be showed to user when Transition button is clicked. Transition contains name and some
additional options. Optionally transition can contain form with list of attributes.

* **Condition** - defines whether specific Transition is allowed with specified input data. Conditions can be nested.

* **Actions** - actions are assigned to Transition and executed when Transition is performed.
There are two kind of actions: Init Action and Post Actions. The difference between them is that
Init Actions are executed before Transition and Post Actions are executed after Transition.
Actions can be used to manage entities (create, find), manipulate attributes (f.e. assign values)
and to perform any other actions.

* **Workflow** - aggregates steps, attributes and transitions. Workflow is a model that doesn't have it's own state
but it can be referred by Workflow Items.

* **Workflow Data** - container aggregated by Workflow Item where each value associated with some Attribute.
Those values can be entered by user directly or assigned via Actions.

* **Workflow Item** - associated with Workflow and indirectly associated with Steps, Transitions and
Attributes. Has it's own state in Workflow Data, current Step and other data. Workflow Item stores entity identifier and entity class that has
associated workflow.

* **TransitionTriggerEvent** - allows to perform transition when needed entity trigger needed Doctrine Event. 

* **TransitionTriggerCron** - allows to perform transition by cron definition. 

How it works?
-------------

Entity can have assigned workflows - it means that on entity view page there will be list of passed steps and
allowed transition buttons. When user clicks button with start transition (and submit transition form if it's exist)
in the background a new instance of Workflow Item of specific Workflow is created.

Each Step has a list of allowed Transitions, and each Transition has list of Conditions that define whether this
Transition can be performed with specific Workflow Item state. If Transition is allowed then user can perform it.
If Transition has Init Actions they are executed before Transition. If transition has Post Actions then
these Post Actions will be performed right after Transition. So, user can move entity through Steps of Workflow until
he reach the final Step where Workflow will be finished.
It's also possible that Workflow doesn't have a final step. In this case user can perform transitions until they are
allowed.

Workflow Item stores all collected data and current step, so, user can stop his progress on Workflow at any moment and
then return to it, and Workflow will have exactly the same state. Each Workflow Item represents workflow
started for some specific entity.

Entity Limitations
==================
To be able to attach an entity to specific workflow (e.g. make entity workflow related) a few criteria should be met. 
- Entity can not have composite fields as its primary keys.
- Entity primary key can be integer or string (for doctrine types it is: BIGINT, DECIMAL, INTEGER, SMALLINT, STRING). In other words - all types that can be casted by SQL CAST to text representation.
- Entity should be configurable see [Annotation](./#annotation) section.

Activation State
----------------

By default all new workflow created in inactive state - it means that there will be no steps and transition
on entity view page. Multiple workflows for each entity can be active in one time.

Activation of workflow can be performed in several ways.

### User Interface

User can activate workflow through UI in Workflow datagrid - it available in menu under "System" > "Workflows".
Here each workflow can be activated either using row actions "Activate" and "Deactivate", or from Workflow view page
using appropriate buttons.

### Configuration

Developer can add workflow configuration corresponded workflow YAML config in sub-node `active` of node `defaults`.
This approach can be used if there is a need to automatically activate workflow on application installation.
Here is example of such configuration:

```YAML
workflows:
    b2b_flow_sales:
        defaults:
            active: true #workflow will be automatically activated during installation
        entity: Oro\Bundle\SalesBundle\Entity\Opportunity
        entity_attribute: opportunity
```

### REST API

WorkflowBundle provides REST API the allows to activate or deactivate workflow.

Activation URL attributes:
* **route:** oro_api_workflow_activate
* **parameter:** workflowDefinition - name of the appropriate workflow

Deactivation URL attributes:
* **route:** oro_api_workflow_deactivate
* **parameter:** workflowDefinition - name of the appropriate workflow

### Workflow Manager

WorkflowBundle has WorkflowManager service (oro_workflow.manager) that provides methods to activate and deactivate
workflows:
* **activateWorkflow(workflowIdentifier)** - activate workflow by workflow name, Workflow instance,
    WorkflowItem instance or WorkflowDefinition instance;
* **deactivateWorkflow(workflowIdentifier)** - deactivate workflow by workflow name, Workflow instance (same as above).

Mutually Exclusive Workflows
----------------------------
In some cases, an application may be configured with several workflows that are mutually exclusive on different levels.
For example, with default package, we have the standard workflow that somehow does not cover business logic that client might need.
So we can implement another workflow for the same related entity and that two workflows are conflicting with each other by data or logic operations. 
For that cases, we bring new approach for developers to configure their workflows on mutually exclusive manner.
There two levels of exclusiveness at this moment: activation level and record level.

###Activation level exclusiveness - `exclusive_active_groups` 
If your custom workflow represents a replacement flow for some already existent workflows you may provide a possibility to secure your customization by ensuring  that only one of them can be activated in the system at a time. This can be performed by defining *common exclusive activation group* for both workflows. That can be done in workflow configuration node named `exclusive_active_groups`.
For example, we have `basic_sales_flow` and `my_shop_sales_flow` workflows.
They are both use the same related entity (let's say Order) and `my_shop_sales_flow` is a full replacement for another one. 
So we need to force administrators to enable only one of them. In that case, we can provide a common group in workflows configurations under `exclusive_active_groups` node. Let's name it 'sales'.
So, now, when an administrator will attempt to activate one of that groups there would be an additional check for group conflicts and notice generated if another workflow in the group 'sales' is already active. So that two workflows would never be active at once.

###Record level exclusiveness - `exclusive_record_groups`
Another level of exclusiveness is a record level. 
This level provides a possibility to have several active workflows at one time with one limitation - only one workflow can be started for a related entity within a same *exclusive record group*. So that if you have workflows that can bring different ways to reach the goal of common business process around same entity (*but* not both at once), you may configure that workflow with the same group in `exclusive_record_groups` at their configurations.
So, when **no** workflows were performed for an entity in same exclusive record group, there would be the possibility to launch starting transitions from any of them. But, when one of that workflows was started - you may not perform any actions from another workflow (and start it as well). That is a ramification of a business process that can be reached by the `exclusive_record_group` node in workflows configuration.

###Priority Case
Let's say, you have two exclusive workflows at the level of a single record and both of them has automated start transitions (e.g. automatically performs start transition when a new instance of their common related entity is created).
In that case, you may configure `priority` flag in workflow configurations so when a new record of the related entity created workflows would be processed by that priority flag and the second one from same exclusive record group will not perform its start transition if there already present another workflow record from the same exclusive group.
For example `first_workflow` and `second_workflow` workflows. In a case when we need to process `second_workflow` workflow before `first_workflow`, we can determine its priority level higher than another.
Then, when new `SomeEntity` entity will be persisted, a system would perform `second_workflow` workflow start transition first.
Additionally, if start transition of dominant workflow has unmet its conditions to start, then the second workflow would have a chance to start its flow as well.

Configuration
-------------

All Workflow entities are described in configuration. Look at example of simple Workflow configuration that performs
some action with User entity.

``` yaml
workflows:
    example_user_flow:                            # name of the workflow
        entity: Oro\Bundle\UserBundle\Entity\User # workflow related entity
        entity_attribute: user                    # attribute name of current entity that can be used in configuration
        start_step: started                       # step that will be assigned automatically to new entities
        steps_display_ordered: true               # defines whether all steps will be shown on view page in steps widget
        defaults:
            active: true                          # active by default
        exclusive_active_groups: [group_flow]     # active only single workflow for a specified groups
        exclusive_record_groups:
            - unique_run                          # only one started workflow for the `entity` from specified groups can exist at time
        priority: 100                             # has priority of 100
        steps:                                    # list of all existing steps in workflow
            started:                              # step where user should enter firstname and lastname
                order: 10                         # order of step (ascending)
                allowed_transitions:              # list of allowed transition from this step
                    - set_name                    # first name and last name should be entered on this transition
            processed:                            # step where user can review entered data
                order: 20                         # steps will be shown in ascending
                allowed_transitions:              # order of step
                   - add_email                    # new email should be added on this transition

        attributes:                                           # list of all existing attributes in workflow
            first_name:                                       # first name of a user
                property_path: user.firstName                 # path to entity property (automatically defined attribute metadata)
            middle_name:                                      # middle name of a user
                property_path: user.middleName                # path to entity property (automatically defined attribute metadata)
            last_name:                                        # last name of a user
                property_path: user.lastName                  # path to entity property (automatically defined attribute metadata)
            email_string:                                     # email string temporary attribute
                type: string                                  # attribute type
            email_entity:                                     # email entity temporary attribute
                type: entity                                  # attribute type
                options:                                      # attribute options
                    class: Oro\Bundle\UserBundle\Entity\Email # entity class name

        transitions:                                        # list of all existing transitions in workflow
            set_name:                                       # transition from step "started" to "processed"
                step_to: processed                          # next step after transition performing
                transition_definition: set_name_definition  # link to definition of conditions and post actions
                form_options:                               # options which will be passed to form type of transition
                    attribute_fields:                       # list of attribute fields which will be shown
                        first_name:                         # attribute name
                            options:                        # list of form field options
                                required: true              # define this field as required
                                constraints:                # list of constraints
                                    - NotBlank: ~           # this field must be filled
                        middle_name: ~                      # attribute name
                        last_name:                          # attribute name
                            options:                        # list of form field options
                                required: true              # define this field as required
                                constraints:                # list of constraints
                                    - NotBlank: ~           # this field must be filled
            add_email:                                      # transition from step "add_email" to "add_email" (self-transition)
                step_to: processed                          # next step after transition performing
                transition_definition: add_email_definition # link to definition of conditions and post actions
                form_options:                               # options which will be passed to form type of transition
                    attribute_fields:                       # list of attribute fields which will be shown
                        email_string:                       # attribute name
                            options:                        # list of form field options
                                required: true              # define this field as required
                                constraints:                # list of constraints
                                    - NotBlank: ~           # this field must be filled
                                    - Email: ~              # field must contain valid email
            schedule_transition:                                            # transition from step "add_email" to "add_email" (self-transition)
                step_to: processed                                          # next step after transition performing
                transition_definition: schedule_transition_definition       # link to definition of conditions and post actions
                triggers:                                                   # transition triggers
                    -
                        cron: '* * * * *'                                   # cron definition
                        filter: "e.someStatus = 'OPEN'"                     # dql-filter
                    -
                        entity_class: Oro\Bundle\SaleBundle\Entity\Quote    # entity class
                        event: update                                       # event type
                        field: status                                       # updated field
                        queued: false                                       # handle trigger not in queue
                        relation: user                                      # relation to Workflow entity
                        require: "entity.status = 'pending'"                # expression language condition

        transition_definitions:                                   # list of all existing transition definitions
            set_name_definition: []                               # definitions for transition "set_name", no extra conditions or actions here
            add_email_definition:                                 # definition for transition "add_email"
                actions:                                          # list of action which will be performed after transition
                    - '@create_entity':                           # create email entity
                        class: Oro\Bundle\UserBundle\Entity\Email # entity class
                        attribute: $email_entity                  # entity attribute that should store this entity
                        data:                                     # data for creating entity
                            email: $email_string                  # entered email
                            user: $user                           # current user
                    - '@call_method':                             # call specific method from entity class
                        object: $user                             # object that should call method
                        method: addEmail                          # method that should be called
                        method_parameters:                        # parameters that will be passed to the called method
                            [$email_entity]                       # add email from temporary attribute
                    - '@unset_value':                             # unset temporary properties
                            [$email_string, $email_entity]        # clear email string and entity
            schedule_transition_definition:                       # definitions for transition "schedule_transition", no extra conditions or actions here
                actions:                                          # list of action which will be performed after transition
                    - '@assign_value': [$user.status, 'processed']# change user's status
```

This configuration describes Workflow that includes two steps - "set_name" and "add_email".

On step "started" user can update full name (first, middle and last name) using transition "set_name".
Then on step "processed" user can add additional emails using transition "add_email".

To perform transition "set_name" user should fill first and last name, and middle name is optional. After this
transition entered data will be automatically set to user through attribute property paths.
And to perform transition "add_email" user must enter valid email - it must be not empty and has valid format.
This transition creates new Email entity with assigned email string and User entity, then adds it to User entity to
create connection and clears temporary attributes in last action.

There are 2 triggers that will try to perform transition `schedule_transition` by cron definition, or when field
`status` of entity with class`Oro\Bundle\SaleBundle\Entity\Quote` will be updated.

Following diagram shows this logic in graphical representation.

![Workflow Diagram](../../images/getting-started_workflow-diagram.png)

**Note:** If you want to test this flow in real application, you can put this configuration in file
Oro/Bundle/UserBundle/Resources/config/oro/workflows.yml, reload definitions using console command
``app/console oro:workflow:definitions:load`` and activate it from UI -
after that you can go to User view page and test it.

Console commands
----------------

WorkflowBundle provides following console commands to work with workflows.

#### oro:workflow:definitions:load

This command loads workflow's configurations from *.yml configuration files to the database. It used
during application installation and update processes. Command has two optional options:

- **--directories** - specifies directories used to find configuration files (multiple values allowed);
- **--workflows** - specifies names of the workflows that should be loaded (multiple values allowed).

**Note:** You must execute this command every time when workflow configurations were changed at "*.yml" files.

#### oro:workflow:transit

This command perform transition with specified name for WorkflowItem with specified ID. It used for performing scheduled
transitions. Command has two required option:

- **--workflow-item** - identifier of WorkflowItem.
- **--transition** - name of Transition.

#### oro:workflow:handle-transition-cron-trigger

This command handles workflow transition cron trigger with specified identifier. Command has one required option:

- **--id** - identifier of the transition cron trigger.
