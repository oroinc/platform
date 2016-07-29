CHANGELOG for BAP-10514
=======================
* Now entity can have more than one active workflows.
* Entity config for `workflow.active_workflow` node were changed to `workflow.active_workflows` array as it now supports multiple active workflows.
* Added single point of workflow activation/deactivation through `@oro_workflow.manager.system_config` (`Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager`) that emits corresponding events (`'oro.workflow.activated'`, `'oro.workflow.deactivated'`).
* Activation or deactivation of workflow now triggers removal for all data in affected entity flows. So when workflow is deactivated or reactivated - WorkflowItems will be removed from storage.
* Controllers methods (REST as well) for activation/deactivation now takes `workflowName` as `WorkflowDefinition` identifier instead of related entity class string.
* Steps retrieval for an entity now returns steps for all currently active workflows for related entity with `workflow` node in each as name of corresponding workflow for steps in `stepsData`. Example: `{"workflowName":{"workflow": "workflowName", "steps":["step1", "step2"], "currentStep": "step1"}}`
* User Interface. If entity has multiply workflows currently active there will be displayed transition buttons for each transition available from all active workflows on entity view page. Flow chart will show as many flows as workflows started for an entity.
* For workflow activation (on workflows datagrid or workflow view page) there would be a popup displayed with field that bring user to pick workflows that should not remain active and supposed to be deactivated (e.g replaced with current workflow).
* Entity datagrids with workflow steps column now will show list of currently started workflows with their steps and filtering by started workflows and their steps is available as well.
* Entity relations for fields `workflowItem` and `workflowStep` (e.g. implementation of `WorkflowAwareInterface`) are forbidden for related entity.
* Added `Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider` class for relations between entities and workflows. Actively used in reports.
* Added support for string identifiers in entities. Previously there was only integers supported as primary keys for workflow related entity.
* Removed `Oro\Bundle\WorkflowBundle\Model\EntityConnector` class and its usage.
* Removed `Oro\Bundle\WorkflowBundle\Model\EntityConnector` dependency form `Oro\Bundle\WorkflowBundle\Model\Workflow` class.
* Added `Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector` class with purpose to check whether entity can be used in workflow as related.
* Entity `Oro\Bundle\WorkflowBundle\Entity\WorkflowItem` now has `entityClass` field with its related workflow entity class name.
* Service '@oro_workflow.manager' (class `Oro\Bundle\WorkflowBundle\Model\WorkflowManager`) was refactored in favor of multiple workflows support.
* Method `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::getApplicableWorkflowByEntityClass` was deprecated and its usage will raise an exception. Usage of `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::getApplicableWorkflows` is recommended instead.
* Interface `Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface` marked as deprecated. Its usage is forbidden.
* Trait `Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareTrait` marked as deprecated. Its usage is forbidden.
* Updated class constructor `Oro\Bundle\WorkflowBundle\Model\Workflow`, first argument is `Oro\Bundle\EntityBundle\ORM\DoctrineHelper`.
* Removed class `Oro\Bundle\WorkflowBundle\Field\FieldProvider` and its usages.
* Removed class `Oro\Bundle\WorkflowBundle\Field\FieldGenerator` and its usages.
* Updated `Oro\Bundle\WorkflowBundle\Model\WorkflowManager`, removed unnecessary calling of `resetWorkflowItem`.
* Updated all Unit Tests to support new `Oro\Bundle\WorkflowBundle\Model\Workflow`
* Definition for `oro_workflow.prototype.workflow` was changed, removed `Oro\Bundle\WorkflowBundle\Model\EntityConnector` dependency
* Updated class constructor `Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder`, removed second argument `$fieldGenerator`
* Updated REST callback `oro_api_workflow_entity_get`, now it uses `oro_entity.entity_provider` service to collect entities and fields
* Removed following services:
    * oro_workflow.field_generator
    * oro_workflow.exclusion_provider
    * oro_workflow.entity_provider
    * oro_workflow.entity_field_provider
    * oro_workflow.entity_field_list_provider
* Removed `Oro\Bundle\WorkflowBundle\Field\FieldGenerator` dependency from class `Oro\Bundle\WorkflowBundle\Model\EntityConnector`
* Removed `Oro\Bundle\WorkflowBundle\Field\FieldGenerator` dependency from class `Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener`, for now all required constants moved to this class
* Added new method `getActiveWorkflowsByEntityClass`, that returns all found workflows for an entity class
* Added new method `hasActiveWorkflowsByEntityClass`, that indicates if an entity class has one or more linked workflows
* Removed method `getActiveWorkflowByEntityClass` from `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry`, use `getActiveWorkflowsByEntityClass`
* Removed method `hasActiveWorkflowByEntityClass` from `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry`, use `hasActiveWorkflowsByEntityClass`
