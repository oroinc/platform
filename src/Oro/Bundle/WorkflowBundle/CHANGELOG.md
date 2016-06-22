CHANGELOG for BAP-10806
=======================
* Updated class constructor `\Oro\Bundle\WorkflowBundle\Model\Workflow`, first argument is `\Oro\Bundle\EntityBundle\ORM\DoctrineHelper`.
* Removed `\Oro\Bundle\WorkflowBundle\Model\EntityConnector` dependency form `\Oro\Bundle\WorkflowBundle\Model\Workflow` class.
* Updated `\Oro\Bundle\WorkflowBundle\Model\WorkflowManager`, removed unnecessary calling of `resetWorkflowItem`.
* Updated all Unit Tests to support new `\Oro\Bundle\WorkflowBundle\Model\Workflow`
* Definition for `oro_workflow.prototype.workflow` was changed, removed `\Oro\Bundle\WorkflowBundle\Model\EntityConnector` dependency

CHANGELOG for BAP-10805
=======================
* Removed class `Oro\Bundle\WorkflowBundle\Field\FieldProvider`
* Removed class `Oro\Bundle\WorkflowBundle\Field\FieldGenerator`
* Updated class constructor `Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder`, removed second argument `$fieldGenerator`
* Updated REST callback `oro_api_workflow_entity_get`, now it uses `oro_entity.entity_provider` service to collect entities and fields
* Removed following services:
    * oro_workflow.field_generator
    * oro_workflow.exclusion_provider
    * oro_workflow.entity_provider
    * oro_workflow.entity_field_provider
    * oro_workflow.entity_field_list_provider
* Removed `Oro\Bundle\WorkflowBundle\Field\FieldGenerator` dependency from class `Oro\Bundle\WorkflowBundle\Model\EntityConnector`
* Removed `Oro\Bundle\WorkflowBundle\Field\FieldGenerator` dependency from class ``\Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener``, for now all required constants moved to this class and should be updated or removed on https://magecore.atlassian.net/browse/BAP-10803

CHANGELOG for BAP-10810
=======================
* Added new method `getActiveWorkflowsByEntityClass`, that returns all found workflows for an entity class
* Added new method `hasActiveWorkflowsByEntityClass`, that indicates if an entity class has one or more linked workflows
* Removed method `getActiveWorkflowByEntityClass` from `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry`, use `getActiveWorkflowsByEntityClass`
* Removed method `hasActiveWorkflowByEntityClass` from `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry`, use `hasActiveWorkflowsByEntityClass`
