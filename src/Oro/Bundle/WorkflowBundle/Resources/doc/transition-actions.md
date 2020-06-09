Transition Actions
==================

Table of Contents
-----------------

 - [Create Related Entity](#create-related-entity)
 - [Start Workflow](#start-workflow)
 - [Transit Workflow](#transit-workflow)


Create Related Entity
---------------------

**Class:** Oro\Bundle\WorkflowBundle\Model\Action\CreateRelatedEntity

**Alias:** create_related_entity

**Description:** Creates workflow related entity with data, persists it to Db and sets it as WorkflowItem entity value.

**Parameters:**
 - data - array of data that should be set to entity.

**Configuration Example**
```
- '@create_related_entity':
    conditions:
        # optional condition configuration
    parameters:
        data:
            result: $conversation_result
            comment: $conversation_comment
            successful: $conversation_successful
            call: $managed_entity

OR

- '@create_entity':
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
- '@start_workflow': # start workflow and create workflow item
    conditions:
        # optional condition configuration
    parameters:
        name: sales
        attribute: $.result.workflowItem
        entity: $.result.opportunity
        transition: develop

OR

- '@start_workflow': # start workflow and create workflow item
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
- '@transit_workflow':
    entity: $opportunity
    transition: develop
    workflow: opportunity_flow
    data:
        budget_amount: 1000
        probability: 0.95
        
OR

- '@transit_workflow':
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

- '@transit_workflow':
    [$opportunity, 'develop', 'opportunity_flow', { budget_amount: 1000, probability: 0.95 }]
```
