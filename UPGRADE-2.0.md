UPGRADE FROM 1.10 to 2.0 
========================

####WorkflowBundle
- Class `Oro\Bundle\WorkflowBundle\Model\WorkflowManager` 
    - construction signature was changed now it takes next arguments:
        - `WorkflowRegistry` $workflowRegistry,
        - `DoctrineHelper` $doctrineHelper,
        - `EventDispatcherInterface` $eventDispatcher,
        - `WorkflowEntityConnector` $entityConnector
    - method `getApplicableWorkflow` was removed -> new method `getApplicableWorkflows` with `$entity` instance was added instead.
    - method `getApplicableWorkflowByEntityClass` was removed. Use `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::getApplicableWorkflows` method instead.
    - method `hasApplicableWorkflowByEntityClass` was removed. Use method `hasApplicableWorkflows` instead with an entity instance.
    - method `getWorkflowItemByEntity` was removed -> new method `getWorkflowItem` with arguments `$entity` and `$workflowName` to retrieve an `WorkflowItem` instance for corresponding entity and workflow.
    - method `getWorkflowItemsByEntity` was added to retrieve all `WorkflowItems` instances from currently active (running) workflows for the entity provided as single argument.
    - method `hasWorkflowItemsByEntity` was added to get whether entity provided as single argument has any active (running) workflows with its `WorkflowItems`.
    - method `setActiveWorkflow` was removed -> method `activateWorkflow` with just one argument as `$workflowName` should be used instead. The method now just ensures that provided workflow should be active.
        - now the method emits event `Oro\Bundle\WorkflowBundle\Event\WorkflowEvents::WORKFLOW_ACTIVATED` if workflow was activated.
    - method `deactivateWorkflow` changed its signature. Now it handle single argument as `$workflowName` to ensure that provided workflow is inactive.
        - now the method emits event `Oro\Bundle\WorkflowBundle\Event\WorkflowEvents::WORKFLOW_DEACTIVATED` if workflow was deactivated.
    - method `resetWorkflowData` was added with `WorkflowDefinition $workflowDefinition` as single argument. It removes from database all workflow items related to corresponding workflow.
- Entity configuration (`@Config()` annotation) sub-node `workflow.active_workflow` was removed in favor of `WorkflowDefinition` field `active`. Now for proper workflow activation through configuration you should use `defaults.active: true` in corresponded workflow YAML config. 
- Class `Oro\Bundle\WorkflowBundle\Model\Workflow` changed constructor signature. First argument `EntityConnector` was replaced by `DoctrineHelper`
    - method `resetWorkflowData` was removed - use `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::resetWorkflowData` instead.
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
- Activation of workflows now provided through `WorkflowManager::activateWorkflow` and `WorkflowManager::deactivateWorkflow` methods as well as with workflow YAML configuration boolean node `defaults.active` to load default activation state from configuration.
    
    **NOTE**: Please pay attention to make activations only through corresponded `WorkflowManager` methods.
            Do **NOT** make direct changes in `WorkflowDefinition::setActive` setter. 
            As `WorkflowManager` is responsive for activation events emitting described above.
     
- Added trait `Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait` with methods:
    * `joinWorkflowItem` - to easily join workflowItem to an entity with QueryBuilder
    * `joinWorkflowStep` - to easily join workflowStep to an entity with QueryBuilder through optionally specified workflowItem alias
        Note: `joinWorkflowStep` internally checks for workflowItem alias join to be already present in QueryBuilder instance to use it or creates new one otherwise.
    * `addDatagridQuery` - for datagrid listeners to join workflow fields (especially workflowStatus)
- Added workflow definition configuration node `exclusive_active_groups` to determine exclusiveness of active state in case with conflicting workflows in system.
- Added workflow definition configuration node `exclusive_record_groups` to determine exclusiveness of currently running workflow for an related entity by named group.  
- Added `WorkflowDefinition` property with workflow YAML configuration node `priority` to be able regulate order of workflow acceptance in cases with cross-functionality.
    For example `workflow_record_group` with two workflows in one group and auto start transition will be sorted by priority and started only one with higher priority value.

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
