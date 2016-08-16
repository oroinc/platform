UPGRADE FROM 1.10 to 2.0 
========================

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
    - method `resetWorkflowItem` was removed
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
- Added trait `Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait` with methods:
    * `joinWorkflowItem` - to easily join workflowItem to an entity with QueryBuilder
    * `joinWorkflowStep` - to easily join workflowStep to an entity with QueryBuilder trough specified workflowItem alias
    * `addDatagridQuery` - for datagrid listeners to join workflow fields (especially workflowStatus)
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
* Added `preconditions` to process definition for use instead of `pre_conditions`
* Added `preconditions` to transition definition for use instead of `pre_conditions`
* Added `form_init` to transition definition for use instead of `init_actions`
* Added `actions` to transition definition for use instead of `post_actions`
* Definitions `pre_conditions`, `init_actions`, `post_actions` marked as deprecated

####LocaleBundle:
- Added helper `Oro\Bundle\LocaleBundle\Helper\LocalizationQueryTrait` for adding needed joins to QueryBuilder
- Added provider `Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider` for providing current localization
- Added manager `Oro\Bundle\LocaleBundle\Manager\LocalizationManager` for providing localizations
- Added datagrid extension `Oro\Bundle\LocaleBundle\Datagrid\Extension\LocalizedValueExtension` for working with localized values in datagrids
- Added datagrid property `Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty`
- Added extension interface `Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface` for providing current localization
- Added twig filter `localized_value` to `Oro\Bundle\LocaleBundle\Twig\LocalizationExtension` for getting localized values in Twig
- Added ExpressionFunction `localized_value` to `Oro\Bundle\LocaleBundle\Layout\ExpressionLanguageProvider` - can be used in Layouts
- Added Localization Settings page in System configuration
- Updated `Oro\Bundle\LocaleBundle\Helper\LocalizationHelper`, used `CurrentLocalizationProvider` for provide current localization and added `getLocalizedValue()` to retrieve fallback values

###Layout Component:
- Interface `Oro\Component\Layout\DataProviderInterface` was removed.
- Abstract class `Oro\Component\Layout\AbstractServerRenderDataProvider` was removed.
- Methods `Oro\Component\Layout\DataAccessorInterface::getIdentifier()` and `Oro\Component\Layout\DataAccessorInterface::get()`  was removed.
- Added class `Oro\Component\Layout\DataProviderDecorator`.
- Add possibility to use parameters in data providers, for details please check out documentation [Layout data](./src/Oro/Bundle/LayoutBundle/Resources/doc/layout_data.md).
- Method `Oro\Component\Layout\ContextDataCollection::getIdentifier()` was removed.
- Twig method `layout_attr_merge` was renamed to `layout_attr_defaults`.
