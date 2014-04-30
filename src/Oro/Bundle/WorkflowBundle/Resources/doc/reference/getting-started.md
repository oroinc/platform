Getting Started
===============

Table of Contents
-----------------
 - [What is Workflow?](#what-is-workflow)
 - [Main Entities](#main-entities)
 - [Entity and Wizard Workflows?](#entity-and-wizard-workflows)
 - [How it works?](#how-it-works)
 - [Managed Entities](#managed-entities)
 - [Bind Entities](#bind-entities)
 - [Configuration](#configuration)

What is Workflow?
=================

Workflow is a complex solution that allows user to perform set of actions with predefined conditions - each next action
depends on previous. Also Workflow can be described as some kind of wizard that helps user to perform complex actions.
Usually Workflow is used to manage some specific entity and to create additional entities.

Main Entities
=============

Workflow consists of several related entities.

* **Step** - entity that shows current status of Workflow. Before rendering each transitions checked
is it allowed for current Workflow Item. Contains name, label and list of allowed transitions.

* **Attribute** - entity that represent one value in Workflow Item, used to render field value on a step form.
Attribute knows about its type (string, object, entity etc.) and additional options.
Contains name and label as additional parameters.

* **Transition** - action that change current step of Workflow Item (i.e. moves it from one step to another). Transition
is allowed if it's Conditions are satisfied. Before Transition performed Init Actions are executed and after
transition performed - Post Actions are executed. Transition can be used as a start transition - it means that this
transition will start Workflow and create new instance of Workflow Item. Transition optionally could have a form. In
this case this form will be showed to user when Transition button is clicked. Transition contains name, label and some
additional options. Optionally can contain form with list of attributes.

* **Condition** - defines whether specific Transition is allowed with specified input data. Conditions can be nested.

* **Actions** - actions are assigned to Transition and executed when Transition performed. There are two kind of actions:
Init Action and Post Actions. The difference between them is that Init Actions are executed before Transition and
Post Actions are executed after Transition. Actions can be used to manage entities (create, find), manipulate attributes
(assign values) and to perform any other actions.

* **Workflow** - aggregates steps, attributes and transitions. Workflow is a model that doesn't have own state but it
can have instances as Workflow Items.

* **Workflow Data** - aggregated by Workflow Item. Each value associated with Attribute.
Those values can be entered by user directly or assigned via Actions.

* **Workflow Item** - associated with Workflow and indirectly associated with Steps, Transitions and
Attributes. Has it's own state in Workflow Data, current Step name, list of bound entities and other data.

Entity and Wizard Workflows
===========================

There are two types of Workflows:
* wizard;
* entity (default).

**Wizard Workflow**

When user starts wizard Workflow then he will be redirected to special Workflow page. On this page he can see next
UI blocks:
* list of steps labels as links
* optional area with form of step
* optional area with view attributes of step
* all other steps and their forms in read only mode
* buttons with possible transitions
* custom blocks configured by developer (for example information block with some entity data)

![Example of Wizard Workflow UI](../images/wizard-workflow-ui-example.png)

**Entity Workflow**

Unlike wizard, entity Workflow doesn't have special page and it's directly managed on entity page. Another difference
from wizard Workflow is that steps of entity Workflow cannot have forms and user performs transitions on managed entity
page by clicking on Workflow buttons.

How it works?
=============

When user clicks button with start transition (and submit transition form if it's exist) in the background
a new instance of Workflow Item of specific Workflow is created.

Each Step has a list of allowed Transitions, and each Transition has list of Conditions that define whether this
Transition can be performed with specific Workflow Item state. If Transition is allowed then user can perform it.
If Transition has Init Actions they are executed before Transition. If transition has Post Actions then
these Post Actions will be performed right after Transition. So, user can move through Steps of Workflow until
he reach the final Step where Workflow will be finished.
It's also possible that Workflow doesn't have a final step. In this case user can perform transitions until they are
allowed.

Workflow Item stores all collected data and current step, so, user can stop his progress on Workflow at any moment and
then return to it, and Workflow will have exactly the same state.

Configuration
=============

All Workflow entities are described in configuration. Look at example of simple Workflow configuration that creates a
new user.

```
workflows:
    example_user_flow:                            # name of the workflow
        label: 'User Workflow Example'            # workflow label
        entity: Oro\Bundle\UserBundle\Entity\User # workflow related entity
        entity_attribute: user                    # attribute name of current entity that can be used in configuration
        start_step: started                       # step that will be shown first
        steps_display_ordered: true               # all workflow steps will be shown on the related entity view page
                                                  # otherwise, only the current step and the past steps will be shown
        steps:                                    # list of all existing steps in workflow
            started:                              # step where user should fill form with firstname and lastname
                label: 'Started'                  # step label
                order: 10                         # steps will be shown in ascending
                allowed_transitions:              # list of allowed transition from this step
                    - set_name                    # firstname and lastname will be filled on this transition
            processed:                            # step where user can review entered data
                label: 'Processed'
                order: 20
                allowed_transitions:
                   - add_email
        attributes:                               # list of all existing attributes in workflow
            middlename:                           # middlename of user
                property_path: user.middleName    # path to entity property (automatically defined attribute metadata)
            email_string:                         # email string attribute
                label: 'Email'                    # attribute label
                type: string                      # attribute type, possible values are:
                                                  # bool (boolean), int (integer), float, string, array, object, entity
            email:                                # email entity
                label: 'Email'
                type: entity
                options:                          # attribute options
                    class: Oro\Bundle\UserBundle\Entity\Email # entity class name
        transitions:                                       # list of all existing transitions in workflow
            set_name:                                      # transition from step "started" to "processed"
                label: 'Set middlename'                    # transition label
                step_to: processed                         # next step after transition performing
                transition_definition: set_name_definition # link to definition of conditions and post actions
                form_options:                              # options which will be passed to form type of transition
                    attribute_fields:                      # list of attribute fields which will be shown
                        middlename:                        # form field name
                            options:                       # list of form field options
                                required: true             # define this field as required
                                constraints:               # list of constraints
                                    - NotBlank: ~          # this field must be filled
            add_email:
                label: 'Add email'
                step_to: processed
                transition_definition: add_email_definition
                form_options:
                    attribute_fields:
                        email_string:
                            options:
                                required: true
                                constraints:
                                    - NotBlank: ~
        transition_definitions:                             # list of all existing transition definitions
            set_name_definition:                            # definitions for transition "set_name"
                conditions:                                 # required conditions: user is not empty
                    @not_empty: $user                       # user attribute value is not empty
                post_actions:                               # list of action which will be performed after transition
                    - @assign_value:                        # list of fields that will be updated
                        - [$user.middlename, $middlename]   # update users middlename
            add_email_definition:
                conditions:
                    @not_empty: $user
                post_actions:                               # list of action which will be performed after transition
                    - @create_entity:                         # create email entity
                        class: Oro\Bundle\UserBundle\Entity\Email # entity class
                        attribute: $email                   # entity attribute in configuration
                        data:                               # data for creating entity
                            email: $email_string
                            user: $user
                    - @call_method:                         # call specific method from entity class
                        object: $user                       # object that should call method
                        method: addEmail                    # method that should be called
                        method_parameters:                  # parameters that will be passed to the called method
                            [$email]
                    - @unset_value:                         # unset temporary properties
                            [$email_string, $email]
```

This configuration describes Workflow that includes two steps - "user_form" and "user_summary".

At step "user_form" user should fill small form with personal information attributes - "username" as text (required),
"age" as integer (required) and "email" as email (optional).

To perform transition "create_user" several conditions must be satisfied (transition definition
"create_user_definition", node "conditions"): user must enter username (condition @not_empty) and (condition @and) age
must be greater of equals to 18 years (condition @greater_or_equal). If these conditions and satisfied following post
actions will be performed (transition definition "create_user_definition", node "post_actions"): User entity will be
created with entered data and it will be saved to attribute "user" (post action @create_entity).

Following diagram shows this schema in graphical representation.

![Workflow Diagram](../images/getting-started_workflow-diagram.png)
