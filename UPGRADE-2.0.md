UPGRADE FROM 1.10 to 2.0 
========================

####ActionBundle
- Class `Oro\Bundle\ActionBundle\Layout\Block\Type\ActionLineButtonsType` was removed -> block type `action_buttons` replaced with DI configuration.
- Added class `Oro\Bundle\ActionBundle\Layout\DataProvider\ActionButtonsProvider` - layout data provider.
- Default value for parameter `applications` in operation configuration renamed from `backend` to `default`.

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
    - method `resetWorkflowItem` was removed
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
* Class `Oro\Bundle\WorkflowBundle\Form\EventListener\InitActionsListener` renamed to `Oro\Bundle\WorkflowBundle\Form\EventListener\FormInitListener`.
* Service 'oro_workflow.form.event_listener.init_actions' renamed to `oro_workflow.form.event_listener.form_init`.
* Fourth constructor argument of class `Oro\Bundle\WorkflowBundle\Form\Type\WorkflowAttributesType` changed from `InitActionsListener $initActionsListener` to `FormInitListener $formInitListener`.
* Added `preconditions` to process definition for use instead of `pre_conditions`
* Added `preconditions` to transition definition for use instead of `pre_conditions`
* Added `form_init` to transition definition for use instead of `init_actions`
* Added `actions` to transition definition for use instead of `post_actions`
* Definitions `pre_conditions`, `init_actions`, `post_actions` marked as deprecated
- Added workflow definition configuration node `exclusive_active_groups` to determine exclusiveness of active state in case with conflicting workflows in system.
- Added workflow definition configuration node `exclusive_record_groups` to determine exclusiveness of currently running workflow for an related entity by named group.
- Added `WorkflowDefinition` property with workflow YAML configuration node `priority` to be able regulate order of workflow acceptance in cases with cross-functionality.
    For example `workflow_record_group` with two workflows in one group and auto start transition will be sorted by priority and started only one with higher priority value.
* Removed service `@oro_workflow.manager.system_config` and its class `Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager` as now there no entity configuration for active workflows. Activation and deactivation of a workflow now should be managed through WorkflowManager (`Oro\Bundle\WorkflowBundle\Model\WorkflowManager`- `@@oro_workflow.manager`)
* Method `getApplicableWorkflows` in `Oro\Bundle\WorkflowBundle\Model\WorkflowManager` now accepts ONLY entity instance. Class name support has been removed.
* Added new interface `WorkflowApplicabilityFilterInterface` with method `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::addApplicabilityFilter(WorkflowApplicabilityFilterInterface $filter)` for ability to additionally filter applicable workflows for an entity.
Used with new class `Oro\Bundle\WorkflowBundle\Model\WorkflowExclusiveRecordGroupFilter` now that represents `exclusive_record_groups` functionality part.
* Added `priority` property to `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition` and workflow configuration to be able configure priorities in workflow applications.
* Added `isActive` property to `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition` instead of EntityConfig
* Added `groups` property to `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition` that contains `WorkflowDefinition::GROUP_TYPE_EXCLUSIVE_ACTIVE` and `WorkflowDefinition::GROUP_TYPE_EXCLUSIVE_RECORD` nodes of array with corresponded groups that `WorkflowDefintiion` is belongs to.
* Added methods `getExclusiveRecordGroups` and `getExclusiveActiveGroups` to `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition`
* `getName`, `getLabel` and `isActive` methods of `Oro\Bundle\WorkflowBundle\Model\Workflow` now are proxy methods to its `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition` instance.
* Removed method `getStartTransitions` from `Oro\Bundle\WorkflowBundle\Model\WorkflowManager` -  `$workflow->getTransitionManager()->getStartTransitions()` can be used instead
* Entity config `workflow.active_workflows` was removed. Use workfow configuration boolean node `defaults.active` instead.

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

####Layout Component:
- Interface `Oro\Component\Layout\DataProviderInterface` was removed.
- Abstract class `Oro\Component\Layout\AbstractServerRenderDataProvider` was removed.
- Methods `Oro\Component\Layout\DataAccessorInterface::getIdentifier()` and `Oro\Component\Layout\DataAccessorInterface::get()`  was removed.
- Added class `Oro\Component\Layout\DataProviderDecorator`.
- Add possibility to use parameters in data providers, for details please check out documentation [Layout data](./src/Oro/Bundle/LayoutBundle/Resources/doc/layout_data.md).
- Method `Oro\Component\Layout\ContextDataCollection::getIdentifier()` was removed.
- Twig method `layout_attr_merge` was renamed to `layout_attr_defaults`.
- BlockType classes replaced with DI configuration for listed block types: `external_resource`, `input`, `link`, `meta`, `ordered_list`, `script` and `style`. Corresponding block type classes was removed.
- Added interface `Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface`
- Added class `Oro\Component\Layout\Extension\Theme\ResourceProvider\ThemeResourceProvider` that implements `Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface`
- Added interface `Oro\Component\Layout\Extension\Theme\Visitor\VisitorInterface`
- Added class `Oro\Component\Layout\Extension\Theme\Visitor\ImportVisitor` that implements `Oro\Component\Layout\Extension\Theme\Visitor\VisitorInterface`
- Added method `Oro\Component\Layout\Extension\Theme\ThemeExtension::addVisitor` for adding visitors that implements `Oro\Component\Layout\Extension\Theme\Visitor\VisitorInterface`
- Added method `Oro\Component\Layout\LayoutUpdateImportInterface::getImport`.
- Added methods `Oro\Component\Layout\Model\LayoutUpdateImport::getParent` and `Oro\Component\Layout\Model\LayoutUpdateImport::setParent` that contains parent `Oro\Component\Layout\Model\LayoutUpdateImport` for nested imports.
- Renamed option for `Oro\Component\Layout\Block\Type\BaseType` from `additional_block_prefix` to `additional_block_prefixes`, from now it contains array.
- Added methods `getRoot`, `getReplacement`, `getNamespace` and `getAdditionalBlockPrefixes` to `Oro\Component\Layout\ImportLayoutManipulator` for working with nested imports.
- Added method `Oro\Component\Layout\Templating\Helper\LayoutHelper::parentBlockWidget` for rendering parent block widget.
- Added method `getUpdateFileNamePatterns` to `Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface`.
- Added method `getUpdateFilenamePattern` to `Oro\Component\Layout\Loader\Driver\DriverInterface`.

####LayoutBundle
- Removed class `Oro\Bundle\LayoutBundle\CacheWarmer\LayoutUpdatesWarmer`.
- Added class `Oro\Bundle\LayoutBundle\EventListener\ContainerListener`, register event `onKernelRequest` that helps to warm cache for layout updates resources.
- Moved layout updates from container to `oro.cache.abstract`
- Added new Twig function `parent_block_widget` to `Oro\Bundle\LayoutBundle\Twig\LayoutExtension` for rendering parent block widget.
- Added interface `Oro\Component\Layout\Form\FormRendererInterface` to add fourth argument `$renderParentBlock` to method `searchAndRenderBlock` that tells renderer to search for widget in parent theme resources.
- Added interface `Oro\Bundle\LayoutBundle\Form\TwigRendererInterface` that extends new `Oro\Component\Layout\Form\FormRendererInterface`.
- Added interface `Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface` that extends `Symfony\Component\Form\FormRendererEngineInterface` to add new method `switchToNextParentResource` needed for `parent_block_widget`.
- Added interface `Oro\Bundle\LayoutBundle\Form\TwigRendererEngineInterface` that extends new `Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface` for using it everywhere in LayoutBundle instead of `Symfony\Bridge\Twig\Form\TwigRendererEngineInterface`.
- Added class `Oro\Bundle\LayoutBundle\Form\BaseTwigRendererEngine` that extends `Symfony\Bridge\Twig\Form\TwigRendererEngine` and implements new `Oro\Bundle\LayoutBundle\Form\TwigRendererEngineInterface`.
- Updated class `Oro\Bundle\LayoutBundle\Form\RendererEngine\TwigRendererEngine` to extend new `Oro\Bundle\LayoutBundle\Form\BaseTwigRendererEngine`.
- Updated class `Oro\Bundle\LayoutBundle\Form\RendererEngine\TemplatingRendererEngine` that extends `Symfony\Component\Form\Extension\Templating\TemplatingRendererEngine` and implements `Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface`.
- Updated class `Oro\Bundle\LayoutBundle\Form\TwigRendererEngine` to extend new `Oro\Bundle\LayoutBundle\Form\BaseTwigRendererEngine`.
- Updated class `Oro\Bundle\LayoutBundle\Layout\TwigLayoutRenderer` to implement `Oro\Bundle\LayoutBundle\Form\TwigRendererInterface`.

####ConfigBundle:
- Class `Oro\Bundle\ConfigBundle\Config\AbstractScopeManager` added `$scopeIdentifier` of type integer, null or object as optional parameter for next methods: `getSettingValue`, `getInfo`, `set`, `reset`, `getChanges`, `flush`, `save`, `calculateChangeSet`, `reload`
- Class `Oro\Bundle\ConfigBundle\Config\ConfigManager` added `$scopeIdentifier` of type integer, null or object as optional parameter for next methods: `get`, `getInfo`, `set`, `reset`, `flush`, `save`, `calculateChangeSet`, `reload`, `getValue`, `buildChangeSet`
- Class `Oro\Component\Config\Loader\FolderContentCumulativeLoader` now uses list of regular expressions as fourth argument instead of list of file extensions. For example if you passed as fourth argument `['yml', 'php']` you should replace it with `['/\.yml$/', '/\.php$/']`

####DatagridBundle:
- Class `Oro/Bundle/DataGridBundle/Provider/ConfigurationProvider.php`
    - construction signature was changed now it takes next arguments:
        - `SystemAwareResolver` $resolver,
        - `CacheProvider` $cache
    - method `warmUpCache` was added to fill or refresh cache.
    - method `loadConfiguration` was added to set raw configuration for all datagrid configs.
    - method `getDatagridConfigurationLoader` was added to get loader for datagrid.yml files.
    - method `ensureConfigurationLoaded` was added to check if datagrid config need to be loaded to cache.
    - You can find example of refreshing datagrid cache in `Oro/Bundle/DataGridBundle/EventListener/ContainerListener.php`
- Added parameter `split_to_cells` to layout `datagrid` block type which allows to customize grid through layouts.

####SecurityBundle
- Removed layout context configurator `Oro\Bundle\SecurityBundle\Layout\Extension\SecurityFacadeContextConfigurator`.
- Added layout context configurator `Oro\Bundle\SecurityBundle\Layout\Extension\IsLoggedInContextConfigurator`.
- Added layout data provider `\Oro\Bundle\SecurityBundle\Layout\DataProvider\CurrentUserProvider` with method `getCurrentUser`, from now use `=data['current_user'].getCurrentUser()` instead of `=context["logged_user"]`.

####EntityExtendBundle
- `Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper`
    - `getEntityClassByTableName` deprecated, use `getEntityClassesByTableName` instead
    - removed property `tableToClassMap` in favour of `tableToClassesMap`
- `Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsBuilder
    - construction signature was changed now it takes next arguments:
        `EntityMetadataHelper` $entityMetadataHelper,
        `FieldTypeHelper` $fieldTypeHelper,
        `ConfigManager` $configManager
    - removed property `tableToEntityMap` in favour of `tableToEntitiesMap`
    - renamed method `getEntityClassName` in favour of `getEntityClassNames`
- `Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsParser`
    - construction signature was changed now it takes next arguments:
        `EntityMetadataHelper` $entityMetadataHelper,
        `FieldTypeHelper` $fieldTypeHelper,
        `ConfigManager` $configManager
