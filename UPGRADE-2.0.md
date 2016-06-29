UPGRADE FROM 1.x to 2.0 
=======================
(todo - replace 1.x with corresponding previous release version of platform)


####WorkflowBundle
- Class `Oro\Bundle\WorkflowBundle\Model\WorkflowManager` construction signature was changed: now it takes `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry`, `Oro\Bundle\EntityBundle\ORM\DoctrineHelper` and `Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager` as arguments.
    - method `getApplicableWorkflow` was removed -> new method `getApplicableWorkflows` with `$entity` (as instance or class name) was added instead.
    - method `getApplicableWorkflowByEntityClass` was marked as deprecated and will throw an exception about "No single workflow supported for an entity" with direction to use `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::getApplicableWorkflows` method instead.
    - method `hasApplicableWorkflowByEntityClass` was renamed to `hasApplicableWorkflowsByEntityClass`.
    - method `getWorkflowItemByEntity` was removed -> new method `getWorkflowItem` with arguments `$entity` and `$workflowName` to retrieve an `WorkflowItem` instance for corresponding entity and workflow.
    - method `getWorkflowItemsByEntity` was added to retrieve all `WorkflowItems` instances from currently active workflow for the entity provided as single argument.
    - method `hasWorkflowItemsByEntity` was added to get whether entity provided as single argument has any active workflows with its `WorkflowItems`.
    - method `setActiveWorkflow` was removed -> method `activateWorkflow` with just one argument as `$workflowIdentifier` should be used instead. The method now just ensures that provided workflow should be active.
    - method `deactivateWorkflow` changed its signature. Now it handle single argument as `$workflowIdentifier` to ensure that provided workflow is inactive.
    - method `resetWorkflowData` was added with `WorkflowDefinition $workflowDefinition` as single argument. It removes from database all workflow items related to corresponding workflow.
- Class `Oro\Bundle\WorkflowBundle\Model\Workflow` changed constructor signature. First argument `EntityConnector` was replaced by `DoctrineHelper`
    - method `resetWorkflowData` was removed - use `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::resetWorkflowData` instead.
- Added class `Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager` with service `oro_workflow.manager.system_config`. Its general purpose is to eliminate points of workflow configuration management as single entry for those purpose.
- Repository `Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository` signature was changed for method `resetWorkflowData` :
    * it requires instance of `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition` as first argument
    * argument `excludedWorkflows` was removed;
- Changed signature of `@transit_workflow` action. Added `workflow_name` parameter as a third parameter in inline call. **Be aware** previously third parameter was `data` parameter. Now `data` is fourth one.
- Service `oro_workflow.entity_connector` (`Oro\Bundle\WorkflowBundle\Model\EntityConnector.php`) removed;
- Parameter `oro_workflow.entity_connector.class` removed;
- Removed parameter `EntityConnector $entityConnector` from constructor of `Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemListener`;
- Removed parameter `EntityConnector $entityConnector` from constructor of `Oro\Bundle\WorkflowBundle\Model\TriggerScheduleOptionsVerifier`;
- Removed form type `Oro\Bundle\WorkflowBundle\Form\Type\ApplicableEntitiesType`;
- Service `oro_workflow.entity_connector` (`Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector`) was added with purpose to check whether entity can be used in workflow as related.
- Now entity can have more than one active workflows.
 