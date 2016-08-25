UPGRADE FROM 1.10 to 2.0 
========================

####ActionBundle
- Class `Oro\Bundle\ActionBundle\Layout\Block\Type\ActionLineButtonsType` was removed -> block type `action_buttons` replaced with DI configuration.
- Added class `Oro\Bundle\ActionBundle\Layout\DataProvider\ActionButtonsProvider` - layout data provider.

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
- Added trait `Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait` with methods:
    * `joinWorkflowItem` - to easily join workflowItem to an entity with QueryBuilder
    * `joinWorkflowStep` - to easily join workflowStep to an entity with QueryBuilder trough specified workflowItem alias
    * `addDatagridQuery` - for datagrid listeners to join workflow fields (especially workflowStatus)

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
- BlockType classes replaced with DI configuration for listed block types: `external_resource`, `input`, `link`, `meta`, `ordered_list`, `script` and `style`. Corresponding block type classes was removed.