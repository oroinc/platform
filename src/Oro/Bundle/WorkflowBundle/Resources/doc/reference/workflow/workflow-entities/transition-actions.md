Transition Actions
=======================

Table of Contents
-----------------
 - [Add Custom Action](#add-custom-action)
 - [Configuration Syntax](#configuration-syntax)
 - [Create Related Entity](#create-related-entity)
 - [Start Workflow](#start-workflow)
 - [Transit Workflow](#transit-workflow)

Add Custom Action
----------------------

To add custom action add a service to DIC with tag "oro_workflow.action", for example:

```
parameters:
    oro_workflow.action.close_workflow.class: Oro\Component\Action\Action\CloseWorkflow
services:
    oro_workflow.action.close_workflow:
        class: %oro_workflow.action.close_workflow.class%
        tags:
            - { name: oro_workflow.action, alias: close_workflow }
```

Symbol "|" in alias can be used to have several aliases. Note that service class must implement
Oro\Component\Action\Action\ActionInterface.

Configuration Syntax
--------------------

Each action can be optionally configured with condition. It allows to implement more sufficient logic in
transitions definitions. If condition is not satisfied action won't be executed.

If flag "break_on_failure" is specified action throws an exception on error, otherwise logs error using standard
logger.

See syntax examples:

**Full Configuration Example**

```
- @alias_of_action:
    conditions:
        # optional condition configuration
    parameters:
        - some_parameters: some_value
        # other parameters of action
    break_on_failure: boolean # by default false
```

**Short Configuration Example**
```
- @alias_of_action:
    - some_parameters: some_value
    # other parameters of action
```


Create Related Entity
---------------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\CreateRelatedEntity

**Alias:** create_related_entity

**Description:** Creates workflow related entity with data, persists it to Db and sets it as WorkflowItem entity value.

**Parameters:**
 - data - array of data that should be set to entity.

**Configuration Example**
```
- @create_related_entity:
    conditions:
        # optional condition configuration
    parameters:
        data:
            result: $conversation_result
            comment: $conversation_comment
            successful: $conversation_successful
            call: $managed_entity

OR

- @create_entity:
    data:
        result: $conversation_result
        comment: $conversation_comment
        successful: $conversation_successful
        call: $managed_entity

```

Start Workflow
--------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\StartWorkflow

**Alias:** start_workflow

**Description:** Triggers start of workflow with configured data. As a result a new WorkflowItem will be produced.

**Parameters:**
 - name - name of Workflow to start;
 - attribute - path where result WorkflowItem will be saved;
 - entity - (optional) path to entity that plays role of managed entity in started Workflow;
 - transition - (optional) name of start transition;

**Configuration Example**
```
- @start_workflow: # start workflow and create workflow item
    conditions:
        # optional condition configuration
    parameters:
        name: sales
        attribute: $.result.workflowItem
        entity: $.result.opportunity
        transition: develop

OR

- @start_workflow: # start workflow and create workflow item
    name: sales
    attribute: $.result.workflowItem
    entity: $.result.opportunity
    transition: develop
```


Transit Workflow
--------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\TransitWorkflow

**Alias:** transit_workflow

**Description:** Performs transition for workflow on a specific entity. Workflow must be already started. 

**Parameters:**
 - entity (or first parameter) - path to entity used for transition operation;
 - transition (or second parameter) - name of the transition;
 - workflow (or third parameter) - name of the workflow;
 - data (or fourth parameter) - (optional) additional data passed to workflow item before transition;
 
**Configuration Example**
```
- @transit_workflow:
    entity: $opportunity
    transition: develop
    workflow: opportunity_flow
    data:
        budget_amount: 1000
        probability: 0.95
        
OR

- @transit_workflow:
    conditions:
        # optional condition configuration
    parameters:
        entity: $opportunity
        transition: develop
        workflow: opportunity_flow
        data:
            budget_amount: 1000
            probability: 0.95
        
OR

- @transit_workflow:
    [$opportunity, 'develop', 'opportunity_flow', { budget_amount: 1000, probability: 0.95 }]
```
