UPGRADE FROM 1.10 to 2.0
========================

#### General
- **Upgrade to 2.0 is available only from 1.10 version**.

  To correctly upgrade to version 2.0 follow the steps in the guide [How to Upgrade to a New Version](https://www.orocrm.com/documentation/index/current/cookbook/how-to-upgrade-to-new-version).
  At **Step 7** instead of running
  ```shell
  $ sudo -u www-data php app/console oro:platform:update --env=prod --force
  ```
  you will run **only once** the upgrade command introduced to help upgrading from 1.10 to 2.0
  ```shell
  $ sudo -u www-data php app/console oro:platform:upgrade20 --env=prod --force
  ```
  
  Upgrade from version less then 1.10 is not supported.

- Changed minimum required php version to 5.6
- PhpUnit 5.7 support
- LiipImagineBundle was updated to 1.5.* version.
- Added dependency to [fxpio/composer-asset-plugin](https://github.com/fxpio/composer-asset-plugin) composer plugin.
- All original third-party asset libraries were moved out from platform and added to composer.json as bower-asset/npm-asset dependency.

#### SOAP API was removed
- removed all dependencies to the `besimple/soap-bundle` bundle.
- removed SOAP annotations from the entities. Updated entities:
    - Oro\Bundle\ActivityListBundle\Entity\ActivityList
    - Oro\Bundle\ActivityListBundle\Entity\ActivityOwner
    - Oro\Bundle\AddressBundle\Entity\AbstractAddress
    - Oro\Bundle\AddressBundle\Entity\AbstractEmail
    - Oro\Bundle\AddressBundle\Entity\AbstractPhone
    - Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress
    - Oro\Bundle\DataAuditBundle\Entity\Audit
    - Oro\Bundle\EmailBundle\Entity\Email
    - Oro\Bundle\EmailBundle\Entity\EmailAttachment
    - Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent
    - Oro\Bundle\EmailBundle\Entity\EmailBody
    - Oro\Bundle\EmailBundle\Entity\EmailFolder
    - Oro\Bundle\EmailBundle\Entity\EmailRecipient
    - Oro\Bundle\EmailBundle\Entity\EmailThread
    - Oro\Bundle\EmailBundle\Entity\EmailUser
    - Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin
    - Oro\Bundle\SearchBundle\Query\Result
    - Oro\Bundle\SearchBundle\Query\Result\Item
- removed classes:
    - Oro\Bundle\ActivityListBundle\Controller\Api\Soap\ActivityListController
    - Oro\Bundle\AddressBundle\Controller\Api\Soap\AddressTypeController
    - Oro\Bundle\AddressBundle\Controller\Api\Soap\CountryController
    - Oro\Bundle\AddressBundle\Controller\Api\Soap\RegionController
    - Oro\Bundle\DataAuditBundle\Controller\Api\Soap\AuditController
    - Oro\Bundle\EmailBundle\Controller\Api\Soap\EmailController
    - Oro\Bundle\NoteBundle\Controller\Api\Soap\NoteController
    - Oro\Bundle\OrganizationBundle\Controller\Api\Soap\BusinessUnitController
    - Oro\Bundle\OrganizationBundle\Controller\Api\Soap\OrganizationController
    - Oro\Bundle\SearchBundle\Controller\Api\SoapController
    - Oro\Bundle\UserBundle\Controller\Api\Soap\GroupController
    - Oro\Bundle\UserBundle\Controller\Api\Soap\RoleController
    - Oro\Bundle\UserBundle\Controller\Api\Soap\UserController
    - Oro\Bundle\AddressBundle\Entity\AddressTypeSoap
    - Oro\Bundle\AddressBundle\Entity\CountrySoap
    - Oro\Bundle\AddressBundle\Entity\RegionSoap
    - Oro\Bundle\NoteBundle\Entity\NoteSoap
    - Oro\Bundle\OrganizationBundle\Entity\BusinessUnitSoap
    - Oro\Bundle\OrganizationBundle\Entity\OrganizationSoap
    - Oro\Bundle\UserBundle\Entity\GroupSoap
    - Oro\Bundle\UserBundle\Entity\RoleSoap
    - Oro\Bundle\UserBundle\Entity\UserSoap
    - Oro\Bundle\TaskBundle\Entity\TaskSoap
    - Oro\Bundle\DataAuditBundle\Entity\AuditData
    - Oro\Bundle\EntityBundle\Model\EntityIdSoap
    - Oro\Bundle\SearchBundle\Soap\Type\SelectedValue
    - Oro\Bundle\SoapBundle\DependencyInjection\Compiler\LoadPass
    - Oro\Bundle\SoapBundle\ServiceDefinition\Loader\OroSoapLoader
    - Oro\Bundle\SoapBundle\ServiceDefinition\Loader\AnnotationComplexTypeLoader
    - Oro\Bundle\SoapBundle\ServiceDefinition\Loader\FilterableLoaderInterface
    - Oro\Bundle\SoapBundle\ServiceDefinition\Loader\ComplexTypeFilterInterface
    - Oro\Bundle\SoapBundle\ServiceDefinition\Loader\SoapAclFieldFilter
    - Oro\Bundle\SoapBundle\Type\KeyValue\StringType
    - Oro\Bundle\AddressBundle\Tests\Functional\API\SoapAddressTypeApiTest
    - Oro\Bundle\AddressBundle\Tests\Functional\API\SoapApiTest
    - Oro\Bundle\DataAuditBundle\Tests\Functional\API\SoapDataAuditApiTest
    - Oro\Bundle\DataAuditBundle\Tests\Functional\Controller\Api\Soap\AuditControllerTest
    - Oro\Bundle\OrganizationBundle\Tests\Functional\API\SoapApiTest
    - Oro\Bundle\SearchBundle\Tests\Functional\Controller\Api\SoapAdvancedSearchApiTest
    - Oro\Bundle\SearchBundle\Tests\Functional\Controller\Api\SoapSearchApiTest
    - Oro\Bundle\SearchBundle\Tests\Functional\Controller\Api\SoapSearchApiTest
    - Oro\Bundle\UserBundle\Tests\Functional\API\SoapApiAclTest
    - Oro\Bundle\UserBundle\Tests\Functional\API\SoapGroupsTest
    - Oro\Bundle\UserBundle\Tests\Functional\API\SoapInvalidUsersTest
    - Oro\Bundle\UserBundle\Tests\Functional\API\SoapRolesTest
    - Oro\Bundle\UserBundle\Tests\Functional\API\SoapUsersACLTest
    - Oro\Bundle\UserBundle\Tests\Functional\API\SoapUsersTest
    - Oro\Bundle\DataAuditBundle\Tests\Unit\Entity\AuditDataTest

#### Action Component
- Deprecated constant `Oro\Component\Action\Event\ExecuteActionEvents::DEPRECATED_HANDLE_BEFORE` removed. Use `Oro\Component\Action\Event\ExecuteActionEvents::HANDLE_BEFORE` instead.
- Deprecated constant `Oro\Component\Action\Event\ExecuteActionEvents::DEPRECATED_HANDLE_AFTER` removed. Use `Oro\Component\Action\Event\ExecuteActionEvents::HANDLE_AFTER` instead.
- Deprecated events `oro_workflow.action.handle_before` and `oro_workflow.action.handle_action` removed.
- Interface Oro\Component\ConfigExpression\ContextAccessorInterface improved. All methods can accept `PropertyPathInterface` and `string`.
- Also updated all methods of Oro\Component\ConfigExpression\ContextAccessor according to changes in Oro\Component\ConfigExpression\ContextAccessorInterface.
- Class `Oro\Component\Action\Model\ContextAccessor`. Use `Oro\Component\ConfigExpression\ContextAccessor` (`@oro_action.expression.context_accessor`) instead.
- Class `Oro\Component\Action\Action\ActionFactory`
    - implements new interface `Oro\Component\ConfigExpression\FactoryWithTypesInterface`
- Class `Oro\Component\Action\Action\FlashMessage`
    - method `setRequest` now accepts null value.

#### Config Expression Component
- Added interface `Oro\Component\ConfigExpression\FactoryWithTypesInterface` with method `FactoryWithTypesInterface::getTypes()`
- Class `Oro\Component\ConfigExpression\ExpressionFactory` now implements interface `Oro\Component\ConfigExpression\FactoryWithTypesInterface`

#### EntitySerializer Component
- Method `isMetadataProperty` of `Oro\Component\EntitySerializer\ConfigUtil` marked as deprecated. Use `isMetadataProperty` of `Oro\Component\EntitySerializer\FieldAccessor` instead

#### OrganizationBundle
- Changed signature of method `Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository::getEnabledOrganizations`.

#### ActionBundle
- Class `Oro\Bundle\ActionBundle\Layout\Block\Type\ActionLineButtonsType` was removed -> block type `action_buttons` replaced with DI configuration.
- Added class `Oro\Bundle\ActionBundle\Layout\DataProvider\ActionButtonsProvider` - layout data provider.
- Default value for parameter `applications` in operation configuration renamed from `backend` to `default`.
- Service `oro_action.context_accessor` removed. Use `oro_action.expression.context_accessor` instead.
- Added new command `Oro\Bundle\ActionBundle\Command\DebugActionCommand (oro:debug:action)` that displays list of all actions with description.
- Added new command `Oro\Bundle\ActionBundle\Command\DebugConditionCommand (oro:debug:condition)` that displays list of all conditions full description
- Command `Oro\Bundle\ActionBundle\Command\DumpActionConfigurationCommand` (`oro:action:configuration:dump`) renamed to `Oro\Bundle\ActionBundle\Command\DebugOperationCommand` (`oro:debug:operation`)
- Tag `oro_workflow.action` removed, now for actions always using `oro_action.action`
- Tag `oro_workflow.condition` removed, now for conditions always using `oro_action.condition`
- Deprecated service `oro_workflow.context_accessor` removed
- Service (`Oro\Bundle\ActionBundle\Model\ConfigurationPass\ReplacePropertyPath`) removed, use `Oro\Component\ConfigExpression\ConfigurationPass\ReplacePropertyPath` instead
- Added `Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface` and trait `Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderTrait`, interface declare methods `isApplicationsValid()` and `getCurrentApplication()`
- Added `Oro\Bundle\ActionBundle\Provider\RouteProviderInterface` and trait `Oro\Bundle\ActionBundle\Provider\RouteProviderTrait`, interface declare getters and setters for `widgetRoute`, `formDialogRoute`, `formPageRoute`, `executionRoute`
- Deleted `Oro\Bundle\ActionBundle\Helper\ApplicationsHelper`.
Please use `Oro\Bundle\ActionBundle\Provider\CurrentApplicationProvider` and `Oro\Bundle\ActionBundle\Provider\RouteProvider` instead.
- Changes in `Oro\Bundle\ActionBundle\Helper\ApplicationsUrlHelper`:
    - type of first argument of `__construct()` changed to `Oro\Bundle\ActionBundle\Provider\RouteProviderInterface`
    - implemented method `getPageUrl()`, that used `ApplicationsHelperInterface::getFormDialogRoute()`
- Type of second argument of `Oro\Bundle\ActionBundle\Helper\DefaultOperationRequestHelper::__construct()` changed to `Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface`
- Type of third argument of `Oro\Bundle\ActionBundle\Model\OperationRegistry::__construct()` changed to `Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface`
- Changes in `Oro\Bundle\ActionBundle\Layout\DataProvider`:
    - type of first argument of `__construct()` changed to `Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface`
    - implemented method `getPageRoute()`
- Added `Oro\Bundle\ActionBundle\Button\ButtonSearchContext`, that wrap parameters needed for searching of a buttons
- Added `Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider` for providing ButtonSearchContext by array context
- Added `Oro\Bundle\ActionBundle\Button\ButtonInterface`, that declare methods for rendering of a button, `getOrder()`, `getTemplate()`, `getTemplateData()`, `getButtonContext()` and `getGroup()`, `getName()`, `getLabel()`, `getIcon()`, `getTranslationDomain()`
- Added `Oro\Bundle\ActionBundle\Button\ButtonContext`, that should be used to provide required context data to `ButtonInterface`
- Added `Oro\Bundle\ActionBundle\Button\ButtonProviderExtensionInterface`, that declare method `find()` for collect buttons from extensions
- Added `Oro\Bundle\ActionBundle\Button\ButtonsCollection` to handle relation between button (ButtonInterface) and its extension (ButtonProviderExtensionInterface), so it can be used in its `map` or `filter` methods as arguments.
    the collection can be used to keep matched buttons under single structure fot further reuse
    - method `filter():ButtonsCollection`filter to a new collection instance by provided filter callback
    - method `map():ButtonsCollection` to a new collection instance through provided map callback
    - method `toList():ButtonInterface[]` gets ordered array of buttons by (ButtonInterface::getOrder())
    - method `toArray():ButtonInterface[]` gets all buttons that handle the collection
    - can be iterated (implements `\IteratorAggregate`) through `\ArrayIterator` of `toList()` result.
    - implements `\Countable`
- Added `Oro\Bundle\ActionBundle\Provider\ButtonProvider` for providing buttons from extensions
    methods:
    - `findAll(ButtonSearchContext):ButtonInterface[]` - search by context through provider, sets ButtonContext enabled flag by availability
    - `findAvailable(ButtonSearchContext):ButtonInterface` - only available buttons
    - `match(ButtonSearchContext):ButtonsCollection` - returns instance that contains matched buttons and its corresponded button extension
    - `hasButtons(ButtonSearchContext):bool` - any matched button met
    - registered as service `oro_action.provider.button`
- Added `Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ButtonProviderPass`, that collect button providers by tag `oro.action.extension.button_provider` and inject it to `oro_action.provider.button`
- Added `Oro\Bundle\ActionBundle\Model\OperationButton`, that implements `ButtonInterface` and specific logic for operation buttons
- Added `Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension`, that provide operation buttons
    - registered by tag `oro.action.extension.button_provider`
- Changed `Oro\Bundle\ActionBundle\Controller\WidgetController::buttonsAction`, now it use `oro_action.provider.button` and `oro_action.provider.button_search_context` to provide buttons
- Added `Oro\Bundle\ActionBundle\Layout\DataProvider\LayoutButtonProvider`, that provide buttons for layouts
    - methods `getAll()` and `getByGroup()`
    - registered as layout provider `button_provider`
- Changes in `Oro\Bundle\ActionBundle\Twig\OperationExtension`:
    - first argument `Oro\Bundle\ActionBundle\Model\OperationManager $manager` of `__construct()` removed
    - type of second argument changed from `Oro\Bundle\ActionBundle\Helper\ApplicationsHelper` to `Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface` and now it first argument
    - added fourth argument `Oro\Bundle\ActionBundle\Provider\ButtonProvider $buttonProvider` of `__construct()`
    - added fifth argument `Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider $searchContextProvider` of `__construct()`
    - added filter `oro_action_has_buttons`, used `Oro\Bundle\ActionBundle\Provider\ButtonProvider` and `Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider`
    - removed filter `has_operations`
- Renamed js component `oroaction/js/app/components/buttons-component` to `oroaction/js/app/components/button-component` (from plural to single)
- Added tag `oro_action.operation_registry.filter` to be able to register custom final filters for `Oro\Bundle\ActionBundle\Model\OperationRegistry::find` result. Custom filter must implement `Oro\Bundle\ActionBundle\Model\OperationRegistryFilterInterface`
- Changed signature for `Oro\Bundle\ActionBundle\Model\OperationRegistry::find` it now accepts only one argument `Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria`.
- Class `Oro\Bundle\ActionBundle\Datagrid\EventListener\OperationListener` renamed to `Oro\Bundle\ActionBundle\Datagrid\EventListener\ButtonListener` to support all buttons from `ButtonProvider`
- Changed constructor dependencies on `\Oro\Bundle\ActionBundle\Datagrid\Extension\DeleteMassActionExtension`
 - third argument `Oro\Bundle\ActionBundle\Model\OperationManager` replaced with `Oro\Bundle\ActionBundle\Model\OperationRegistry` and additional `Oro\Bundle\ActionBundle\Helper\ContextHelper` as fourth argument.
- Class `Oro\Bundle\ActionBundle\Form\Type\OperationType` first constructor argument (OperationManager) removed.
- Class `Oro\Bundle\ActionBundle\Model\OperationManager` with service `oro_action.operation_manager` removed. Use:
- `Oro\Bundle\ActionBundle\Model\OperationRegistry` (`@oro_action.operation_registry`) to retrieve an Operation directly
- `Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension` (`@oro_action.provider.button.extension.operation`) for OperationButtons
- `Oro\Bundle\ActionBundle\Provider\ButtonProvider` (`@oro_action.provider.button`) - to operate all buttons
- Changed signature of constructor of `Oro\Bundle\ActionBundle\Datagrid\EventListener\ButtonListener`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.
- Changed signature of constructor of `Oro\Bundle\ActionBundle\Datagrid\Extension\DeleteMassActionExtension`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.

#### ApiBundle
- The `oro.api.action_processor` DI tag was removed. To add a new action processor, use `oro_api.actions` section of the ApiBundle configuration.
- The `oro_api.config_extension` DI tag was removed. To add a new configuration extension, use `oro_api.config_extensions` section of the ApiBundle configuration.

#### ImportExportBundle
- ImportExportBundle/Field/FieldHelper.php was moved to EntityBundle/Helper/
- Added fourth argument `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider $entityConfigProvider` to `__construct` method of `Oro\Bundle\ImportExportBundle\Handler\AbstractHandler`.
- Added fifth argument `Symfony\Component\Translation\TranslatorInterface $translator` to `__construct` method of `Oro\Bundle\ImportExportBundle\Handler\AbstractHandler`.
- Removed method `public function setTranslator(TranslatorInterface $translator)` from `Oro\Bundle\ImportExportBundle\Handler\AbstractImportHandler`.
- Method `setSourceIterator` of `Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader` now can set null value.

#### WorkflowBundle
- Class `Oro\Bundle\WorkflowBundle\Model\WorkflowManager`
    - construction signature was changed, now it takes the next arguments:
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
        - the method emits event `Oro\Bundle\WorkflowBundle\Event\WorkflowEvents::WORKFLOW_BEFORE_ACTIVATION` before workflow will be activated.
        - the method emits event `Oro\Bundle\WorkflowBundle\Event\WorkflowEvents::WORKFLOW_ACTIVATED` if workflow was activated.
    - method `deactivateWorkflow` changed its signature. Now it handles single argument as `$workflowName` to ensure that provided workflow is inactive.
        - the method emits event `Oro\Bundle\WorkflowBundle\Event\WorkflowEvents::WORKFLOW_BEFORE_DEACTIVATION` before workflow will be deactivated.
        - the method emits event `Oro\Bundle\WorkflowBundle\Event\WorkflowEvents::WORKFLOW_DEACTIVATED` if workflow was deactivated.
    - method `resetWorkflowData` was added with `WorkflowDefinition $workflowDefinition` as single argument. It removes from database all workflow items related to corresponding workflow.
    - method `resetWorkflowItem` was removed
- Entity configuration (`@Config()` annotation) sub-node `workflow.active_workflow` was removed in favor of `WorkflowDefinition` field `active`. Now for proper workflow activation through configuration you should use `defaults.active: true` in corresponded workflow YAML config.
- Class `Oro\Bundle\WorkflowBundle\Model\Workflow` changed constructor signature. First argument `EntityConnector` was replaced by `DoctrineHelper`
    - method `resetWorkflowData` was removed - use `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::resetWorkflowData` instead.
- Repository `Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository` signature was changed for method `resetWorkflowData` :
    * it requires instance of `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition` as the first argument
    * argument `excludedWorkflows` was removed;
- Changed signature of `@transit_workflow` action. Added `workflow_name` parameter as a the third parameter in inline call. **Be aware** previously the third parameter was `data` parameter. Now `data` is the fourth one.
- Service `oro_workflow.entity_connector` (`Oro\Bundle\WorkflowBundle\Model\EntityConnector.php`) was removed;
- Parameter `oro_workflow.entity_connector.class` was removed;
- Removed parameter `EntityConnector $entityConnector` from constructor of `Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemListener`;
- Removed form type `Oro\Bundle\WorkflowBundle\Form\Type\ApplicableEntitiesType`;
- Service `oro_workflow.entity_connector` (`Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector`) was added with purpose to check whether entity can be used in workflow as related.
- Now entity can have more than one active workflows.
- Activation of workflows is now provided through `WorkflowManager::activateWorkflow` and `WorkflowManager::deactivateWorkflow` methods as well as with workflow YAML configuration boolean node `defaults.active` to load default activation state from configuration.

    **NOTE**: Please pay attention to make activations only through corresponded `WorkflowManager` methods.
            Do **NOT** make direct changes in `WorkflowDefinition::setActive` setter.
            As `WorkflowManager` is responsive for activation events emitting described above.

- Added trait `Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait` with methods:
    * `joinWorkflowItem` - to easily join workflowItem to an entity with QueryBuilder
    * `joinWorkflowStep` - to easily join workflowStep to an entity with QueryBuilder through optionally specified workflowItem alias
        Note: `joinWorkflowStep` internally checks for workflowItem alias join to be already present in QueryBuilder instance to use it or creates new one otherwise.
    * `addDatagridQuery` - for datagrid listeners to join workflow fields (especially workflowStatus)
* Activation or deactivation of workflow now triggers removal for all data in affected entity flows. So when workflow is deactivated or reactivated - WorkflowItems will be removed from storage.
* Controllers methods (REST as well) for activation/deactivation now takes `workflowName` as `WorkflowDefinition` identifier instead of related entity class string.
* Steps retrieval for an entity now returns steps for all currently active workflows for related entity with `workflow` node in each as name of corresponding workflow for steps in `stepsData`. Example: `{"workflowName":{"workflow": "workflowName", "steps":["step1", "step2"], "currentStep": "step1"}}`
* User Interface. If an entity has multiply workflows currently active, transition buttonswill be displayed for each transition available from all active workflows on the entity view page. Flow chart will show as many flows as workflows started for an entity.
* For workflow activation (on workflows datagrid or workflow view page) there would be a popup displayed with a field that brings user to pick workflows that should not remain active and are supposed to be deactivated (e.g replaced with current workflow).
* Entity datagrids with workflow steps column now will show a list of currently started workflows with their steps and filtering by started workflows and their steps is available as well.
* Entity relations for fields `workflowItem` and `workflowStep` (e.g. implementation of `WorkflowAwareInterface`) are forbidden for related entity.
* Added `Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider` class for relations between entities and workflows. Actively used in reports.
* Added support for string identifiers in entities. Previously, there was only integers supported as primary keys for workflow related entity.
* Removed `Oro\Bundle\WorkflowBundle\Model\EntityConnector` class and its usage.
* Removed `Oro\Bundle\WorkflowBundle\Model\EntityConnector` dependency form `Oro\Bundle\WorkflowBundle\Model\Workflow` class.
* Added `Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector` class with purpose to check whether entity can be used in workflow as related.
* Entity `Oro\Bundle\WorkflowBundle\Entity\WorkflowItem` now has `entityClass` field with its related workflow entity class name.
* Service '@oro_workflow.manager' (class `Oro\Bundle\WorkflowBundle\Model\WorkflowManager`) was refactored in favor of multiple workflows support.
* Method `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::getApplicableWorkflowByEntityClass` removed. Use `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::getApplicableWorkflows` instead.
* Interface `Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface` should no longer be used for entities. It completely changed.
* Trait `Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareTrait` removed.
* Removed class `Oro\Bundle\WorkflowBundle\Field\FieldProvider` and its usages.
* Removed class `Oro\Bundle\WorkflowBundle\Field\FieldGenerator` and its usages.
* Definition for `oro_workflow.prototype.workflow` was changed, removed `Oro\Bundle\WorkflowBundle\Model\EntityConnector` dependency.
* Updated class constructor `Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder`, removed second argument `$fieldGenerator`.
* Updated REST callback `oro_api_workflow_entity_get`, now it uses `oro_entity.entity_provider` service to collect entities and fields.
* Removed following services:
    * oro_workflow.field_generator
    * oro_workflow.exclusion_provider
    * oro_workflow.entity_provider
    * oro_workflow.entity_field_provider
    * oro_workflow.entity_field_list_provider
* Removed `Oro\Bundle\WorkflowBundle\Field\FieldGenerator` dependency from class `Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener`, for now all required constants moved to this class
* Added a new method `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry::getActiveWorkflowsByEntityClass`, that returns all found workflows for an entity class
* Added a new method `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry::hasActiveWorkflowsByEntityClass`, that indicates if an entity class has one or more linked workflows
* Removed method `getActiveWorkflowByEntityClass` from `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry`, use `getActiveWorkflowsByEntityClass` instead.
* Removed method `hasActiveWorkflowByEntityClass` from `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry`, use `hasActiveWorkflowsByEntityClass` instead.
* Class `Oro\Bundle\WorkflowBundle\Form\EventListener\InitActionsListener` renamed to `Oro\Bundle\WorkflowBundle\Form\EventListener\FormInitListener`.
* Service 'oro_workflow.form.event_listener.init_actions' was renamed to `oro_workflow.form.event_listener.form_init`.
* Fourth constructor argument of class `Oro\Bundle\WorkflowBundle\Form\Type\WorkflowAttributesType` was changed from `InitActionsListener $initActionsListener` to `FormInitListener $formInitListener`.
* Added `preconditions` to process definition to be used instead of `pre_conditions`
* Added `preconditions` to transition definition to be used instead of `pre_conditions`
* Added `form_init` to transition definition to be used instead of `init_actions`
* Added `actions` to transition definition to be used instead of `post_actions`
* Definitions `pre_conditions`, `init_actions`, `post_actions` marked as deprecated
- Added workflow definition configuration node `exclusive_active_groups` to determine exclusiveness of active state in case of conflicting workflows in the system.
- Added workflow definition configuration node `exclusive_record_groups` to determine exclusiveness of currently running workflow for a related entity by named group.
- Added `WorkflowDefinition` property with workflow YAML configuration node `priority` to be able regulate order of workflow acceptance in cases with cross-functionality.
    For example `workflow_record_group` with two workflows in one group and auto start transition will be sorted by priority and started only one with higher priority value.
* Removed service `@oro_workflow.manager.system_config` and its class `Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager` as now there no entity configuration for active workflows. Activation and deactivation of a workflow now should be managed through WorkflowManager (`Oro\Bundle\WorkflowBundle\Model\WorkflowManager`- `@@oro_workflow.manager`)
               * Added new interface `WorkflowApplicabilityFilterInterface` with method `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::addApplicabilityFilter(WorkflowApplicabilityFilterInterface $filter)` for ability to additionally filter applicable workflows for an entity.
Used with new class `Oro\Bundle\WorkflowBundle\Model\WorkflowExclusiveRecordGroupFilter` now represents `exclusive_record_groups` functionality part.
* Added `priority` property to `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition` and workflow configuration to be able configure priorities in workflow applications.
* Added `isActive` property to `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition` instead of EntityConfig
* Added `groups` property to `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition` that contains `WorkflowDefinition::GROUP_TYPE_EXCLUSIVE_ACTIVE` and `WorkflowDefinition::GROUP_TYPE_EXCLUSIVE_RECORD` nodes of array with corresponding groups that `WorkflowDefintiion` is belongs to.
* Added methods `getExclusiveRecordGroups` and `getExclusiveActiveGroups` to `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition`
* `getName`, `getLabel` and `isActive` methods of `Oro\Bundle\WorkflowBundle\Model\Workflow` now are proxy methods to its `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition` instance.
* Removed method `getStartTransitions` from `Oro\Bundle\WorkflowBundle\Model\WorkflowManager` -  `$workflow->getTransitionManager()->getStartTransitions()` can be used instead
* The command `oro:process:execute:job` was removed.
* Processes configuration file now loads from `Resorces/config/oro/processes.yml` file instead of `Resources/config/oro/process.yml`
* Processes configuration in `oro/processes.yml` file now gathered under `processes: ...` root node.
- `oro_workflow.repository.workflow_item` inherits `oro_entity.abstract_repository`.
- Service `oro_workflow.generator.trigger_schedule_options_verifier` (`Oro\Bundle\WorkflowBundle\Model\TriggerScheduleOptionsVerifier`) removed.
- Service `oro_workflow.transition_schedule.process_configuration_generator` (`Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator`) removed.
- Service `oro_workflow.transition_schedule.items_fetcher` (`Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ItemsFetcher`) removed.
- Service `oro_workflow.transition_schedule.query_factory` (`Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\TransitionQueryFactory`) removed.
- Service `oro_workflow.cache.process_trigger` (`Oro\Bundle\WorkflowBundle\Cache\ProcessTriggerCache`) removed.
- Model `Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcessName` removed.
- Class `Oro\Bundle\WorkflowBundle\Model\ProcessTriggerCronScheduler` was moved to `Oro\Bundle\WorkflowBundle\Cron\ProcessTriggerCronScheduler` and constructor signature was changed to `DeferredScheduler $deferredScheduler`.
- Added new entity `Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger`.
- Added new entity `Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger`.
- Added new interface `Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface`.
- Added new interface `Oro\Bundle\WorkflowBundle\Entity\Repository\EventTriggerRepositoryInterface`.
- Added new command `oro:workflow:handle-transition-cron-trigger` to handle transition cron trigger.
- Removed schedule feature for workflow transitions. Now triggers can be used for schedule transitions.
- Removed listener `Oro\Bundle\WorkflowBundle\EventListener\ProcessCollectorListener`.
- Removed parameter `oro_workflow.listener.process_collector.class`.
- Removed listener `oro_workflow.event_listener.scheduled_transitions_listener` (`Oro\Bundle\WorkflowBundle\EventListener\WorkflowScheduledTransitionsListener`).
- Removed action group `oro_workflow_transition_process_schedule`.
- Added possibility of translation for workflow configuration fields in file `Resources/translations/workflows.en.yml`:
Now all following fields MUST be moved from workflow.yml configuration file in appropriate translation file under its key node. See keys and fields below:
 `oro.workflow.{workflow_name}.label` - workflow `label` field.
 `oro.workflow.{workflow_name}.step.{step_name}.label` - step `label` field.
 `oro.workflow.{workflow_name}.attribute.{attribute_name}.label` - workflow attribute `label` field.
 `oro.workflow.{workflow_name}.transition.{transition_name}.label` - transition `label` field.
 `oro.workflow.{workflow_name}.transition.{transition_name}.warning_message` - transition `message` field.
 `oro.workflow.{workflow_name}.transition.{transition_name}.attribute.{attribute_name}.label` - form options attribute `label` field.
To migrate all labels from configuration translatable fields automatically you can use application command `oro:workflow:definitions:upgrade20`.
- Added command `oro:workflow:translations:dump` as a helper to see custom workflow translations (lack of them as well) and their keys.
- Added `Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface` and `oro.workflow.definition_builder.extension` service tag for usage in cases to pre-process (prepare) workflow builder configuration.
- Added service tag `oro.workflow.configuration.handler` for request configuration procession by `Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface`.
- Removed `import` method from `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition`. Use `Oro\Bundle\WorkflowBundle\Handler\Helper\WorkflowDefinitionCloner::cloneDefinition` instead.
- Added `originalDefinition` property and second constructor argument for `Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent` in case of definition update.
- Container parameter `oro_workflow.workflow_item.entity.class` renamed to `oro_workflow.entity.workflow_item.class`
- Container parameter `oro_workflow.workflow_definition.entity.class` renamed to `oro_workflow.entity.workflow_definition.class`
- Container parameter `oro_workflow.process_trigger.entity.class` renamed to `oro_workflow.entity.process_trigger.class`
- Container parameter `oro_workflow.process_definition.entity.class` renamed to `oro_workflow.entity.process_definition.class`
- Added container parameter `oro_workflow.entity.transition_trigger_cron.class`
- Added container parameter `oro_workflow.entity.transition_trigger_event.class`
- Changed signature of constructor of `\Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionSelectType`, now it takes as second argument instance of `Symfony\Component\Translation\TranslatorInterface`
- Changed signature of constructor of `\Oro\Bundle\WorkflowBundle\Form\Type\WorkflowSelectType`, now it takes as second argument instance of `\Symfony\Component\Translation\TranslatorInterface`
- Changed signature of constructor of `\Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType`, now it takes as second argument instance of `\Symfony\Component\Translation\TranslatorInterface`
- Deprecated service `oro_workflow.attribute_guesser` removed.
- Deprecated interfaces and classes removed:
  * `Oro\Bundle\WorkflowBundle\Model\AbstractStorage`
  * `Oro\Bundle\WorkflowBundle\Model\ConfigurationPass\ReplacePropertyPath` (service `oro_workflow.configuration_pass.replace_property_path`)
  * `Oro\Bundle\WorkflowBundle\Model\ReplacePropertyPath\ContextAccessor`
  * `Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvent`
  * `Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvents`
  * `Oro\Bundle\WorkflowBundle\Exception\ActionException`
  * `Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException`
  * `Oro\Bundle\WorkflowBundle\Model\AbstractAssembler`
  * `Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction`
  * `Oro\Bundle\WorkflowBundle\Model\Action\AbstractDateAction`
  * `Oro\Bundle\WorkflowBundle\Model\Action\ActionAssembler`
  * `Oro\Bundle\WorkflowBundle\Model\Action\ActionFactory`
  * `Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface`
  * `Oro\Bundle\WorkflowBundle\Model\Action\AssignActiveUser`
  * `Oro\Bundle\WorkflowBundle\Model\Action\AssignConstantValue`
  * `Oro\Bundle\WorkflowBundle\Model\Action\AssignValue`
  * `Oro\Bundle\WorkflowBundle\Model\Action\CallMethod`
  * `Oro\Bundle\WorkflowBundle\Model\Action\Configurable`
  * `Oro\Bundle\WorkflowBundle\Model\Action\CopyTagging`
  * `Oro\Bundle\WorkflowBundle\Model\Action\CreateDate`
  * `Oro\Bundle\WorkflowBundle\Model\Action\CreateDateTime`
  * `Oro\Bundle\WorkflowBundle\Model\Action\CreateEntity`
  * `Oro\Bundle\WorkflowBundle\Model\Action\CreateObject`
  * `Oro\Bundle\WorkflowBundle\Model\Action\EventDispatcherAwareActionInterface`
  * `Oro\Bundle\WorkflowBundle\Model\Action\FlashMessage`
  * `Oro\Bundle\WorkflowBundle\Model\Action\FormatName`
  * `Oro\Bundle\WorkflowBundle\Model\Action\FormatString`
  * `Oro\Bundle\WorkflowBundle\Model\Action\Redirect`
  * `Oro\Bundle\WorkflowBundle\Model\Action\RemoveEntity`
  * `Oro\Bundle\WorkflowBundle\Model\Action\RequestEntity`
  * `Oro\Bundle\WorkflowBundle\Model\Action\TranslateAction`
  * `Oro\Bundle\WorkflowBundle\Model\Action\Traverse`
  * `Oro\Bundle\WorkflowBundle\Model\Action\TreeExecutor`
  * `Oro\Bundle\WorkflowBundle\Model\Action\UnsetValue`
  * `Oro\Bundle\WorkflowBundle\Model\Attribute`
  * `Oro\Bundle\WorkflowBundle\Model\AttributeGuesser`
  * `Oro\Bundle\WorkflowBundle\Model\AttributeManager`
  * `Oro\Bundle\WorkflowBundle\Model\Condition\AbstractCondition`
  * `Oro\Bundle\WorkflowBundle\Model\Condition\Configurable`
- Added new node `sopes` to `Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration`.
- Added method `Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository::getScopedByNames(array $names, ScopeCriteria $scopeCriteria)`.
- Added ManyToMany relation from `WorkflowDefinition` to `Oro\Bundle\ScopeBundle\Entity\Scope`.
- Added interface `Oro\Bundle\WorkflowBundle\Model/Filter\WorkflowDefinitionFilterInterface.` It can be used for extensions of `WorkflowRegisty`.
- Removed third constructor argument `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider $configProvider` of `WorkflowRegistry`.
- Added method `public function addDefinitionFilter(WorkflowDefinitionFilterInterface $definitionFilter)` to `WorkflowRegistry`.
- Added listener `oro_workflow.listener.workflow_definition_scope` for creating or updating `WorflowDefinition` to update `WorkflowScope` entities.
- Changes in `Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration`:
    - added constants `NODE_INIT_ENTITIES`, `NODE_INIT_ROUTES`, `NODE_INIT_CONTEXT_ATTRIBUTE`, `DEFAULT_INIT_CONTEXT_ATTRIBUTE`
    - added nodes for constants `NODE_INIT_ENTITIES`, `NODE_INIT_ROUTES`, `NODE_INIT_CONTEXT_ATTRIBUTE` into `transitions`
- Changes in `Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder`:
    - added processing of a init context from all transitions (`NODE_INIT_ENTITIES`, `NODE_INIT_ROUTES`, `NODE_INIT_CONTEXT_ATTRIBUTE`, `NODE_INIT_DATAGRIDS`)
    - added nodes `NODE_INIT_ENTITIES`, `NODE_INIT_ROUTES`, `NODE_INIT_CONTEXT_ATTRIBUTE`, `NODE_INIT_DATAGRIDS` into white list of a transition configuration filter
- Changed `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::startAction`, now it use transition init options and `oro_action.provider.button_search_context`
- Changed `Oro\Bundle\WorkflowBundle\Controller\WidgetController::startTransitionFormAction`, now it use transition init options and `oro_action.provider.button_search_context`
- Changed `Oro\Bundle\WorkflowBundle\Controller\WorkflowController::startTransitionAction`, now it use transition init options
- Added method `findActive()` to `Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository`
- Added `Oro\Bundle\WorkflowBundle\Extension\TransitionButtonProviderExtension` as `Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface`, that provide transition buttons
- Added `Oro\Bundle\WorkflowBundle\Extension\StartTransitionButtonProviderExtension` as `Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface`, that provide start transition buttons
- Added `Oro\Bundle\WorkflowBundle\Button\TransitionButton`
- Added `Oro\Bundle\WorkflowBundle\Button\StartTransitionButton`
- Changed `Oro\Bundle\WorkflowBundle\Model\AttributeAssembler::assemble`, now it processing WorkflowConfiguration::NODE_INIT_CONTEXT_ATTRIBUTE
- Changed `Oro\Bundle\WorkflowBundle\Model\Transition`, added properties $initEntities, $initRoutes, $initContextAttribute and getters/setters for it
- Changed `Oro\Bundle\WorkflowBundle\Model\TransitionAssembler::assembleTransition`, now it process NODE_INIT_ENTITIES, NODE_INIT_ROUTES, NODE_INIT_CONTEXT_ATTRIBUTE
- Added `Oro\Bundle\WorkflowBundle\Model\TransitionButton`, that implements `ButtonInterface` and specific logic for transition buttons
- Changed `Oro\Bundle\WorkflowBundle\Model\Workflow`, added methods `getInitEntities()` and `getInitRoutes()` to obtain the appropriate options from the configuration
- Changed `Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler::assembleAttributes`, now it pass transition configuration into `AttributeAssembler::assemble()`
- Added method `getActiveWorkflows()` to `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry`
- Added class `Oro\Bundle\WorkflowBundle\Filter\WorkflowOperationFilter` and registered as an additional (tag: `oro_action.operation_registry.filter`) filter for OperationRegistry.
- Added class `Oro\Bundle\WorkflowBundle\Form\Handler\TransitionFormHandler`
- Added class `Oro\Bundle\WorkflowBundle\Provider\TransitionDataProvider`
- Added class `Oro\Bundle\WorkflowBundle\Provider\WorkflowDataProvider`
- Changed signature of constructor of `Oro\Bundle\WorkflowBundle\Datagrid\Extension\RestrictionsExtension`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.
- Changed signature of constructor of `Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener`. The argument `EntityClassResolver $entityClassResolver` was added.

#### LocaleBundle:
- Added helper `Oro\Bundle\LocaleBundle\Helper\LocalizationQueryTrait` for adding necessary joins to QueryBuilder
- Added provider `Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider` for providing current localization
- Added manager `Oro\Bundle\LocaleBundle\Manager\LocalizationManager` for providing localizations
- Added datagrid extension `Oro\Bundle\LocaleBundle\Datagrid\Extension\LocalizedValueExtension` for working with localized values in datagrids
- Added datagrid property `Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty`
- Added extension interface `Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface` for providing current localization
- Added twig filter `localized_value` to `Oro\Bundle\LocaleBundle\Twig\LocalizationExtension` for getting localized values in Twig
- Added ExpressionFunction `localized_value` to `Oro\Bundle\LocaleBundle\Layout\ExpressionLanguageProvider` - can be used in Layouts
- Added Localization Settings page in System configuration
- Updated `Oro\Bundle\LocaleBundle\Helper\LocalizationHelper`, used `CurrentLocalizationProvider` to provide current localization and added `getLocalizedValue()` to retrieve fallback values
- Changed signature of constructor of `Oro\Bundle\LocaleBundle\Form\Type\LanguageType` - now it takes the following arguments:
    - `ConfigManager $cm`,
    - `LanguageProvider $languageProvider`.
- `oro_locale.repository.localization` inherits `oro_entity.abstract_repository`
- Updated moment-timezone.js library to version 0.5.*
- Changed signature of constructor of `Oro\Bundle\LocaleBundle\Datagrid\Extension\LocalizedValueExtension`. The argument `EntityClassResolver $entityClassResolver` was added.
- Removed methods `getRootEntityNameAndAlias` and `getEntityClassName` from `Oro\Bundle\LocaleBundle\Datagrid\Extension\LocalizedValueExtension`

#### SearchBundle
- Changed all `private` fields and accessors to `protected` in `Oro/Bundle/SearchBundle/Entity/IndexDecimal`, `Oro/Bundle/SearchBundle/Entity/IndexInteger`,
`Oro/Bundle/SearchBundle/Entity/IndexText`, `Oro/Bundle/SearchBundle/Entity/Item`
- Constructor of class `Oro/Bundle/Engine/FulltextIndexManager` was changed. New optional arguments `$tableName` and `$indexName` was added.
- Methods `PdoMysql::getPlainSql` and `PdoPgsql::getPlainSql` were changed. New optional arguments `$tableName` and `$indexName` was added
- `\Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider::getEntityConfig` returns empty array if config not found
- `\Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider::getEntityModeConfig` default value is Mode::NORMAL if configurations is mepty
- `\Oro\Bundle\SearchBundle\Engine\ObjectMapper::mapSelectedData` returns empty array if data fields not found
- `\Oro\Bundle\SearchBundle\Query\Result\Item::_construct` signature changed, array type hintings added
- Constructor of class `Oro\Bundle\SearchBundle\Datagrid\Extension\SearchResultsExtension` was changed. Dependency on `Doctrine\ORM\EntityManager` was removed.
- Constructor of class `Oro\Bundle\SearchBundle\Query\Result\Item` was changed. Dependency on `Doctrine\ORM\EntityManager` was removed.
- Method `getEntity` was removed from `Oro\Bundle\SearchBundle\Query\Result\Item`.
- Changed signature of the constructor of `Oro\Bundle\SearchBundle\EventListener\ORM\FulltextIndexListener`. Removed `$databaseDriver` parameter.
- Changed signature of the constructor of `Oro\Bundle\SearchBundle\EventListener\ORM\FulltextIndexListener`. Added `Connection $connection` parameter.

#### OroIntegrationBundle:
- The option `--integration-id` renamed to `--integration` in `oro:cron:integration:sync` cli command.
- The option `--force` were removed from `oro:cron:integration:sync` cli command. Pass it as connector option  `force=true`.
- The option `--transport-batch-size force` were removed from `oro:cron:integration:sync` cli command.
- The option `--params` were removed from `oro:integration:reverse:sync` cli command. Use `--connector-parameters` instead.
- The `SyncScheduler::schedule` method signature was changed.
- The `GenuineSyncScheduler::schedule` method signature was changed.
- The parameter `oro_integration.genuine_sync_scheduler.class` was removed.
- The parameter `oro_integration.reverse_sync.processor.class` was removed.

#### Layout Component:
- Interface `Oro\Component\Layout\DataProviderInterface` was removed.
- Abstract class `Oro\Component\Layout\AbstractServerRenderDataProvider` was removed.
- Methods `Oro\Component\Layout\DataAccessorInterface::getIdentifier()` and `Oro\Component\Layout\DataAccessorInterface::get()`  were removed.
- Added class `Oro\Component\Layout\DataProviderDecorator`.
- Add possibility to use parameters in data providers, for details please check out documentation [Layout data](./src/Oro/Bundle/LayoutBundle/Resources/doc/layout_data.md).
- Method `Oro\Component\Layout\ContextDataCollection::getIdentifier()` was removed.
- Twig method `layout_attr_merge` was renamed to `layout_attr_defaults`.
- BlockType classes replaced with DI configuration for listed block types: `external_resource`, `input`, `link`, `meta`, `ordered_list`, `script` and `style`. Corresponding block type classes were removed.
- Added interface `Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface`
- Added class `Oro\Component\Layout\Extension\Theme\ResourceProvider\ThemeResourceProvider` that implements `Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface`
- Added interface `Oro\Component\Layout\Extension\Theme\Visitor\VisitorInterface`
- Added class `Oro\Component\Layout\Extension\Theme\Visitor\ImportVisitor` that implements `Oro\Component\Layout\Extension\Theme\Visitor\VisitorInterface`
- Added method `Oro\Component\Layout\Extension\Theme\ThemeExtension::addVisitor` for adding visitors that implements `Oro\Component\Layout\Extension\Theme\Visitor\VisitorInterface`
- Added method `Oro\Component\Layout\LayoutUpdateImportInterface::getImport`.
- Added methods `Oro\Component\Layout\Model\LayoutUpdateImport::getParent` and `Oro\Component\Layout\Model\LayoutUpdateImport::setParent` that contains parent `Oro\Component\Layout\Model\LayoutUpdateImport` for nested imports.
- Renamed option for `Oro\Component\Layout\Block\Type\BaseType` from `additional_block_prefix` to `additional_block_prefixes`, from now it contains array.
- Added methods `getRoot`, `getReplacement`, `getNamespace` and `getAdditionalBlockPrefixes` to `Oro\Component\Layout\ImportLayoutManipulator` to work with nested imports.
- Added method `Oro\Component\Layout\Templating\Helper\LayoutHelper::parentBlockWidget` to render parent block widget.
- Added method `getUpdateFileNamePatterns` to `Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface`.
- Added method `getUpdateFilenamePattern` to `Oro\Component\Layout\Loader\Driver\DriverInterface`.
- Added `Oro\Component\Layout\Block\Type\Options` class that wraps the `array` of options and can evaluate option type (is `option` instanceof `Expression`).
- Updated method `Oro\Component\Layout\Extension\Theme\Visitor::loadImportUpdate()` to add imported updates to updates list right after parent update instead of adding it to the end of updates list.
- Updated `Oro\Component\Layout\BlockTypeInterface`, `Oro\Component\Layout\BlockTypeExtensionInterface`, `Oro\Component\Layout\LayoutRegistryInterface` to use the `Options` object instead of `array`.
- Added method `Oro\Component\Layout\BlockView::getId`.
- Added method `Oro\Component\Layout\ContextInterface::getHash`.
- Added config loader `\Oro\Component\Layout\Config\Loader\LayoutUpdateCumulativeResourceLoader` that tracks directory structure/content updates and files modification time.
- Added interface `Oro\Component\Layout\BlockViewCacheInterface`.
- Added class `Oro\Component\Layout\BlockViewCache`.
- Updated method `Oro\Component\Layout\LayoutBuilder::getLayout`. Added layout cache.
- Injected `BlockViewCache` into `LayoutFactoryBuilder` and passed as argument to `LayoutFactory`. From `LayoutFactory` `BlockViewCache` argument passed as argument to `LayoutBuilder` to save/fetch layout cache data.
- Update interface method arguments `Oro\Component\Layout\BlockFactoryInterface::createBlockView` - removed `$rootId`.

#### LayoutBundle
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
- Added class `Oro\Bundle\LayoutBundle\Layout\Block\Extension\DataCollectorExtension` that collects layout debug information in data collector used in Layouts section of Symfony Profiler.
- Class `Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider` added to provide available image types collected from all themes
- Class `Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader` added to dynamically load Imagine filters
- Dependency injection tag `layout.image_filter.provider` added to support custom Imagine filter providers
- Removed class `Oro\Bundle\LayoutBundle\Layout\Block\Extension\ExpressionExtension`.
- Removed class `Oro\Bundle\LayoutBundle\Layout\Block\Extension\OptionValueBagExtension`.
- Class `Oro\Bundle\LayoutBundle\Exception\CircularReferenceException` moved to `Oro\Component\Layout\Exception\CircularReferenceException`.
- Class `Oro\Bundle\LayoutBundle\Layout\Encode\ExpressionEncoderInterface` moved to `Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderInterface`.
- Class `Oro\Bundle\LayoutBundle\Layout\Encoder\ExpressionEncoderRegistry` moved to `Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderRegistry`.
- Class `Oro\Bundle\LayoutBundle\Layout\Encoder\JsonExpressionEncoder` moved to `Oro\Component\Layout\ExpressionLanguage\Encoder\JsonExpressionEncoder`.
- Class `Oro\Bundle\LayoutBundle\ExpressionLanguage\ExpressionManipulator` moved to `Oro\Component\Layout\ExpressionLanguage\ExpressionManipulator`.
- Class `Oro\Bundle\LayoutBundle\Layout\Processor\ExpressionProcessor` moved to `Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor`.
- All logic that work with `\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor` and related blocks was moved to `EmbeddedFormBundle`:
    * `Oro\Bundle\LayoutBundle\Layout\Form\AbstractFormAccessor` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Form\AbstractFormAccessor`
    * `Oro\Bundle\LayoutBundle\Layout\Form\ConfigurableFormAccessorInterface` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Form\ConfigurableFormAccessorInterface`
    * `Oro\Bundle\LayoutBundle\Layout\Form\DependencyInjectionFormAccessor` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Form\DependencyInjectionFormAccessor`
    * `Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessor`
    * `Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface`
    * `Oro\Bundle\LayoutBundle\Layout\Form\FormAction` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAction`
    * `Oro\Bundle\LayoutBundle\Layout\Form\FormLayoutBuilder` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormLayoutBuilder`
    * `Oro\Bundle\LayoutBundle\Layout\Form\FormLayoutBuilderInterface` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormLayoutBuilderInterface`
    * `Oro\Bundle\LayoutBundle\Layout\Form\GroupingFormLayoutBuilder` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Form\GroupingFormLayoutBuilder`
    * `Oro\Bundle\LayoutBundle\Layout\Block\Type\AbstractFormType` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\AbstractFormType`
    * `Oro\Bundle\LayoutBundle\Layout\Block\Type\FormEndType` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormEndType`
    * `Oro\Bundle\LayoutBundle\Layout\Block\Type\FormFieldType` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormFieldType`
    * `Oro\Bundle\LayoutBundle\Layout\Block\Type\FormFieldsType` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormFieldsType`
    * `Oro\Bundle\LayoutBundle\Layout\Block\Type\FormStartType` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormStartType`
    * `Oro\Bundle\LayoutBundle\Layout\Block\Type\FormType` moved to `Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormType`
- Added layout block type `Oro\Bundle\LayoutBundle\Layout\Block\Type\FormType` with new logic.
- Layout block types `form_start`, `form_end`, `form_fields` is created as `configurable` with DI configuration.
- Added class `Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\BlockViewSerializerNormalizersPass` that collect serializers by tag `layout.block_view_serializer.normalizer` and inject it to `oro_layout.block_view_serializer`:
    * Added block view normalizer `Oro\Bundle\LayoutBundle\Layout\Serializer\BlockViewNormalizer`
    * Added block view normalizer `Oro\Bundle\LayoutBundle\Layout\Serializer\ExpressionNormalizer`
    * Added block view normalizer `Oro\Bundle\LayoutBundle\Layout\Serializer\OptionValueBagNormalizer`
- Added exception class `Oro\Bundle\LayoutBundle\Exception\UnexpectedBlockViewVarTypeException`.
- Added layout context configurator `Oro\Bundle\LayoutBundle\Layout\Extension\LastModifiedDateContextConfigurator`.

#### ConfigBundle:
- Class `Oro\Bundle\ConfigBundle\Config\AbstractScopeManager` added `$scopeIdentifier` of type integer, null or object as optional parameter for next methods: `getSettingValue`, `getInfo`, `set`, `reset`, `getChanges`, `flush`, `save`, `calculateChangeSet`, `reload`
- Class `Oro\Bundle\ConfigBundle\Config\ConfigManager` added `$scopeIdentifier` of type integer, null or object as optional parameter for next methods: `get`, `getInfo`, `set`, `reset`, `flush`, `save`, `calculateChangeSet`, `reload`, `getValue`, `buildChangeSet`
- Class `Oro\Component\Config\Loader\FolderContentCumulativeLoader` now uses list of regular expressions as fourth argument instead of list of file extensions. For example, if you passed as fourth argument `['yml', 'php']` you should replace it with `['/\.yml$/', '/\.php$/']`
- System configuration now loads from `Resources/config/oro/system_configuration.yml` instead of `Resources/config/system_configuration.yml` file.
- Root node for system configuration in `Resources/config/oro/system_configuration.yml` file were changed from `oro_system_configuration` to `system_configuration`.
- Form type `Oro\Bundle\ConfigBundle\Form\Type\ConfigFileType` added to allow file management in the system configuration

#### AttachmentBundle:
- Class `Oro\Bundle\AttachmentBundle\Resizer\ImageResizer` introduced to resize images by filter name
- Removed constant `GRID_LEFT_JOIN_PATH` from `Oro\Bundle\AttachmentBundle\EventListener\AttachmentGridListener`

#### DatagridBundle:
- Class `Oro/Bundle/DataGridBundle/Provider/ConfigurationProvider.php`
    - construction signature was changed now it takes next arguments:
        - `SystemAwareResolver` $resolver,
        - `CacheProvider` $cache
    - method `warmUpCache` was added to fill or refresh cache.
    - method `loadConfiguration` was added to set raw configuration for all datagrid configs.
    - method `getDatagridConfigurationLoader` was added to get loader for datagrid.yml files.
    - method `ensureConfigurationLoaded` was added to check if datagrid config need to be loaded to cache.
    - You can find example of refreshing datagrid cache in `Oro/Bundle/DataGridBundle/EventListener/ContainerListener.php`
- Class `Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource.php`
    - construction signature was changed, now it takes next arguments:
        `ConfigProcessorInterface` $processor,
        `EventDispatcherInterface` $eventDispatcher,
        `ParameterBinderInterface` $parameterBinder,
        `QueryHintResolver` $queryHintResolver
- Added parameter `split_to_cells` to layout `datagrid` block type which allows to customize grid through layouts.
- Configuration files for datagrids now loads from `Resources/config/oro/datagrids.yml` file instead of `Resources/config/datagrid.yml`.
- Configuration files root node now changed to its plural form `datagrids: ...`.
- Added class `Oro\Bundle\DataGridBundle\Extension\Action\Action\ExportAction`
- Added class `Oro\Bundle\DataGridBundle\Extension\Action\Action\ImportAction`
- Added class `Oro\Bundle\DataGridBundle\Extension\Action\Action\AbstractImportExportAction`
- Added class `Oro\Bundle\DataGridBundle\Datasource\Orm\Configs\YamlProcessor`
- Added interface `Oro\Bundle\DataGridBundle\Datasource\Orm\Configs\ConfigProcessorInterface`
- `Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource::getParameterBinder` was deprecated
- `Oro\Bundle\DataGridBundle\Datasource\ParameterBinderAwareInterface::getParameterBinder` was deprecated
- Class `Oro/Bundle/DataGridBundle/Extension/MassAction/DeleteMassActionHandler.php`
    - construction signature was changed now it takes new argument:
        `MessageProducerInterface` $producer
- Added helper `Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper`
- Class `Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction\ActionWidgetAction` renamed to `Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction\ActionWidgetAction\ButtonWidgetAction`
- Removed class `Oro\Bundle\DataGridBundle\EventListener\AbstractDatagridListener`
- Removed constant `DATASOURCE_BIND_PARAMETERS_PATH` from `Oro\Bundle\DataGridBundle\EventListener\DatasourceBindParametersListener`
- Changed signature of constructor of `Oro\Bundle\DataGridBundle\Extension\Board\Processor\DefaultProcessor`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.
- Changed signature of constructor of `Oro\Bundle\DataGridBundle\Extension\Board\BoardExtension`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.
- Changed signature of constructor of `Oro\Bundle\DataGridBundle\Extension\Board\RestrictionManager`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.
- Removed constant `CONFIG_EXTENDED_ENTITY_KEY` from `Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration`
- Changed signature of constructor of `Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionExtension`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.
- Class `Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper` was marked as deprecated. Use `config->getOrmQuery()->getRootEntity()` and `config->getOrmQuery()->getRootAlias()` instead
- Method `addSelect` of `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration` was marked as deprecated. Use `config->getOrmQuery()->addSelect()` instead
- Method `joinTable` of `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration` was marked as deprecated. Use `config->getOrmQuery()->addInnerJoin()` or `config->getOrmQuery()->addLeftJoin()` instead

#### SecurityBundle
- Removed layout context configurator `Oro\Bundle\SecurityBundle\Layout\Extension\SecurityFacadeContextConfigurator`.
- Added layout context configurator `Oro\Bundle\SecurityBundle\Layout\Extension\IsLoggedInContextConfigurator`.
- Added layout data provider `\Oro\Bundle\SecurityBundle\Layout\DataProvider\CurrentUserProvider` with method `getCurrentUser`, from now use `=data['current_user'].getCurrentUser()` instead of `=context["logged_user"]`.
- ACLs configuration file now loads from `Resources/config/oro/acls.yml` file instead of `Resources/config/oro/acls.yml` file
- ACLs configuration file now has root node in their structure named `acls`. So all ACLs should be placed under the root.
- Removed unused properties `$metadataCache`, `$maskBuilderClassNames`, `$permissionToMaskBuilderIdentity` and `$maskBuilderIdentityToPermissions` from `Oro\Bundle\SecurityBundle\Acl\Extension\FieldAclExtension`.
- Removed methods `getMaskBuilderConst` and `getPermissionsForIdentity` from `Oro\Bundle\SecurityBundle\Acl\Extension\FieldAclExtension`.
- Removed methods `setEntityOwnerAccessor` and `fixMaxAccessLevel` from `Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension`. The accessor is injected via constructor.
- Removed `Oro\Bundle\SecurityBundle\Acl\Extension\OwnershipDecisionMakerInterface`. Use `Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface` instead.
- Removed unused method `getSystemLevelClass` from `Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface`.
- Class `Oro\Bundle\SecurityBundle\Acl\Domain\EntityObjectReference` marked as deprecated. Use `Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference` instead.
- Removed unused class `Oro\Bundle\SecurityBundle\Acl\Extension\BaseEntityMaskBuilder`.
- Changed signature of `setTriggeredMask` method of `Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategyContextInterface`. Added `int $accessLevel` parameter.
- Removed method `isMasksComparable` of `Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategy`. This was done by performance reasons.
- Changed signature of the constructor of `Oro\Bundle\SecurityBundle\Acl\Extension\FieldAclExtension`. Removed `$entityClassResolver` parameter. Parameter `ConfigProvider $configProvider` replaced with `ConfigManager $configManager`.
- Changed signature of the constructor of `Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider`. Added `EntityClassResolver $entityClassResolver` parameter.
- Changed signature of the constructor of `Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository`. Removed `$translator` parameter.
- Changed signature of the constructor of `Oro\Bundle\SecurityBundle\Metadata\ActionMetadataProvider`. Added `TranslatorInterface $translator` parameter.
- Changed signature of the constructor of `Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider`. Added `TranslatorInterface $translator` parameter.

#### ImportExportBundle
- Added new event `AFTER_JOB_EXECUTION`, for details please check out [documentation](./src/Oro/Bundle/ImportExportBundle/Resources/doc/reference/events.md).
- For `Oro\Bundle\ImportExportBundle\Job\JobExecutor` added new public method `setEventDispatcher` for setting Event Dispatcher.
- Options for import/export buttons configuration `dataGridName` was renamed to `datagridName`

#### TranslationBundle
- Added controller `Oro\Bundle\TranslationBundle\Controller\LanguageController` to manage Languages.
- Added controller `Oro\Bundle\TranslationBundle\Controller\TranslationController` to manage Translations.
- Added `Oro\Bundle\TranslationBundle\Controller\Api\Rest\TranslationController::updateAction` to update translations.
- Removed controller `Oro\Bundle\TranslationBundle\Controller\ServiceController`.
- Added entity `Oro\Bundle\TranslationBundle\Entity\Language`.
- Added import and export features for translations.
- Added class `Oro\Bundle\TranslationBundle\Provider\LanguageProvider` to get available and enabled languages.
- Added class `Oro\Bundle\TranslationBundle\Helper\LanguageHelper` with helpers-methods for managing Languages.
- Class `Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider`:
    - In method `download` removed argument `$toApply` and the class accepts following arguments now:
        - `string $pathToSave`,
        - `array $projects`,
        - `string $locale (default null)`.
    - Added method `loadTranslatesFromFile` for loading translations from file. Arguments:
        - `string $pathToSave`,
        - `string $locale (default null)`.
- Removed form `Oro\Bundle\TranslationBundle\Form\Type\AvailableTranslationsConfigurationType`.
- Removed twig extension `Oro\Bundle\TranslationBundle\Twig\TranslationStatusExtension`.
- Added new command "oro:translation:load", that allows to transfer all translations from files into Database
- Added entity `Oro\Bundle\TranslationBundle\Entity\TranslationKey`
- Updated entity `Oro\Bundle\TranslationBundle\Entity\Translation`
    - added constant SCOPE_INSTALLED
    - used relation to `Oro\Bundle\TranslationBundle\Entity\TranslationKey` instead of `key` and `domain` fields
    - used relation to `Oro\Bundle\TranslationBundle\Entity\Language` instead of `code` field
- Added entity repository `Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository`
- Removed methods from entity repository `Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository`:
    - `findValues()`
    - `findAvailableDomains()`
    - `findAvailableDomainsForLocales()`
    - `saveValue()`
    - `renameKey()`
    - `copyValue()`
    - `getCountByLocale()`
    - `deleteByLocale()`
- Added interface `Oro\Bundle\TranslationBundle\Extension\TranslationContextResolverInterface`
- Added default translation context resolver `Oro\Bundle\TranslationBundle\Extension\TranslationContextResolver`
- Added translation context provider `Oro\Bundle\TranslationBundle\Provider\TranslationContextProvider`
- Added custom datagrid filter `Oro\Bundle\TranslationBundle\Filter\LanguageFilter`, that allows to handle available language choices for the dropdown.
- Added custom datagrid filter form type `\Oro\Bundle\TranslationBundle\Form\Type\Filter\LanguageFilterType`, that displays only enabled and available languages.
- Added constructor for `Oro\Bundle\TranslationBundle\ImportExport\Serializer\TranslationNormalizer`, now it takes an instance of `Oro\Bundle\TranslationBundle\Manager\TranslationManager`
- Added new manager `Oro\Bundle\TranslationBundle\Manager\TranslationManager`, that provides all required functionality to work with Translation and related entities.
- Added new ACL permission `TRANSLATE`, should be used to determine if user has access to modify translations per language.
- Removed `Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface`
- Added `Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationContextResolverPass`.
- Added `Oro\Bundle\TranslationBundle\Helper\TranslationHelper` class with `oro_translation.helper.translation` as accessor for translation values in database.
- Added Twig extension `\Oro\Bundle\TranslationBundle\Twig\TranslationExtension` wich declare following TWIG functions:
    - `oro_translation_debug_translator`
    - `translation_grid_link`
- Added `Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator`
- Added `Oro\Bundle\TranslationBundle\Translation\TranslationKeySourceInterface` with 2 types of implementations `Oro\Bundle\TranslationBundle\Translation\KeySource\DynamicTranslationKeySource` and immutable one - `Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource`
- Added `Oro\Bundle\TranslationBundle\Translation\TranslationFieldsIteratorInterface` as useful way to define single point of custom structure translatable fields awareness and manipulation.
- Added `Oro\Bundle\TranslationBundle\Translation\TranslationFieldsIteratorTrait`.
- Added Data Provider `Oro\Bundle\TranslationBundle\Layout\DataProvider\TranslatorProvider` that provides the translator to Layouts.
- Added helper `Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper`.
- Changed signature of constructor of `Oro\Bundle\TranslationBundle\EventListener\Datagrid\LanguageListener`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.


#### EntityExtendBundle
- Extend fields default mode is `Oro\Bundle\EntityConfigBundle\Entity\ConfigModel::MODE_READONLY`
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
- Entity extend configuration now loads from `Resources/conig/oro/entity_extend.yml` file instead of `Resources/config/entity_extend.yml`
- Root node for entity extend configuration in file `Resources/conig/oro/entity_extend.yml` were changed from `oro_entity_extend` to `entity_extend`
- `Oro\Bundle\EntityExtendBundle\Command\CacheCommand::setClassAliases` no longer throws `\ReflectionException`
- `Oro\Bundle\EntityExtendBundle\OroEntityExtendBundle::checkConfigs` and `Oro\Bundle\EntityExtendBundle\OroEntityExtendBundle::initializeCache`
throws `\RuntimeException` if cache initialization failed. Make sure you don't autoload extended entity classes during container compilation.
- `cache_warmer` is decorated to allow disable cache warming during extend commands calls. Tag your warmer with `oro_entity_extend.warmer`
tag if it works with extend classes
- Changed `Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer`, now it use `Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper` to save translations instead of `Doctrine\Common\Persistence\ManagerRegistry` and `Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache`.
- `Oro\Bundle\EntityExtendBundle\EventListener\ExtendFieldValueRenderListener::getValueForCollection` always return array
- `Oro\Bundle\EntityExtendBundle\Grid\AbstractFieldsExtension` added support of to-one relations
- Method `get*TargetEntities` is generated as deprecated for both `many-to-many` and `many-to-one` associations.
- Changed signature of auto-generated `get*Targets` method of `many-to-many` association. The parameter `$targetClass` is optional now. If this parameter is not specified this method returns all target entities without filtering them by type.
- Removed constant `EXTEND_ENTITY_CONFIG_PATH` from `Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension`
- Method `addManyToOneRelationTargetSide` of `Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder` was marked as deprecated because it is not used anywhere.


#### ApiBundle:
- API configuration file now loads from `Resources/config/oro/api.yml` instead of `Resources/config/api.yml`.
- `Resources/config/oro/api.yml` root node were renamed from `oro_api` to `api`.

#### QueryDesignerBundle:
- YAML Configuration for query designer now loads from `Resources/config/oro/query_designer.yml` file instead of `Resources/config/query_designer.yml`.

#### TestFrameworkBundle:
- Behat elements now loads from `Resources/config/oro/behat.yml` file instead of `Resources/config/behat_elements.yml`.
- `Oro\Bundle\TestFrameworkBundle\Test\Client::requestGrid` accepts route to test grid as optional last argument. Request pushed to `@request_stack` for proper request emulation
- Added `Oro\Bundle\TestFrameworkBundle\Test\Stub\CallableStub` to be able to easily mock callbacks.

#### ChartBundle:
- Charts configurations now loads from `Resources/config/oro/charts.yml` file instead of `Resources/config/oro/chart.yml`.
- Root node for charts configuration in `Resources/config/oro/charts.yml` file were changed from `oro_chart` to `charts`.

#### IntegrationBundle:
- Integration configuration file now loads from `Resources/config/oro/integrations.yml` file instead of `Resources/config/integration_settings.yml`.
- Root node for integration config file `Resources/config/oro/integrations.yml` were changed from `oro_integration` to `integrations`.
- The `Oro\Bundle\IntegrationBundle\Command\ReverseSyncCommand` command was removed.

#### EntityConfigBundle:
- Entity configuration now loads from `Resources/config/oro/entity_config.yml` file instead of `Resources/config/entity_config.yml`.
- Root node for entity configuration in file `Resources/config/oro/entity_config.yml` were changed from `oro_entity_config` to `entity_config`.
- Constructor of `Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper` changed. Now it takes as first argument instance of `Oro\Bundle\TranslationBundle\Manager\TranslationManager` and second argument still instance of `Symfony\Component\Translation\TranslatorInterface`.
- Changed `Oro\Bundle\EntityConfigBundle\Form\EventListener\ConfigSubscriber`, now it use `Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper` to save translations instead of `Doctrine\Common\Persistence\ManagerRegistry` and `Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache`.
- Changed `Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType`, now it use `Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper` to save translations.
- `Oro\Bundle\EntityConfigBundle\Config\ConfigManager::flush` now flushes $models only
- Class `Oro\Bundle\EntityConfigBundle\Twig\ConfigExtension`
    - construction signature was changed now it takes next arguments:
        - `ConfigManager` $configManager,
        - `RouterInterface` $router,
        - `EntityClassNameHelper` $entityClassNameHelper,
        - `DoctrineHelper` $doctrineHelper

#### HelpBundle:
- Help configuration now loads from `Resources/config/oro/help.yml` instead of `Resources/config/oro_help.yml` file.
- Root node `help` were added for help configuration in `Resources/config/oro/help.yml` file.

#### SearchBundle:
- Search configuration now loads from `Resources/config/oro/search.yml` instead of `Resources/config/search.yml` file.
- Root node `search` were added for search configuration in `Resources/config/oro/search.yml` file.
- `oro_search.entity.repository.search_index` marked as lazy
- Search `\Oro\Bundle\SearchBundle\Query\Query::addSelect()` and `\Oro\Bundle\SearchBundle\Query\Query::select()` have been extended to support the SQL aliasing syntax.
- `\Oro\Bundle\SearchBundle\Query\IndexerQuery` has grown to have an interface `\Oro\Bundle\SearchBundle\Query\SearchQueryInterface` and an abstract base class with common operations. New operations in the interface, highly encouraged to use them: `addSelect`, `setFrom`, `setWhere`.
- `\Oro\Bundle\SearchBundle\Datagrid\Extension\Pager\IndexerPager` is no longer depending on IndexerQuery.
- `\Oro\Bundle\SearchBundle\Datasource\SearchDatasource` has now improved alignment with the `\Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource` and is moved to the `Oro\Bundle\SearchBundle\Datasource` namespace.
- Search Query is now created by `\Oro\Bundle\SearchBundle\Query\Factory\QueryFactory`.
- using own, customized Query wrappers, instead of IndexerQuery now possible, by replacing QueryFactory with own factory `\Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface` object.
- new Extensions added: `\Oro\Bundle\SearchBundle\Datagrid\Extension\Pager\SearchPagerExtension` (extending the Orm version), `\Oro\Bundle\SearchBundle\Datagrid\Extension\SearchFilterExtension` (common part with the Orm version).
- `\Oro\Bundle\SearchBundle\Datagrid\Extension\SearchFilterExtension` makes it possible to use search filters together with a new `\Oro\Bundle\SearchBundle\Datagrid\Datasource\Search\SearchFilterDatasourceAdapter`.
- `\Oro\Bundle\SearchBundle\Datagrid\Datasource\Search\SearchFilterDatasourceAdapter` does not rely on the Doctrine's ExpressionBuilder. Using `expr()` discouraged in favor of `Criteria::expr()`.
- filters are now loaded per Datasource, by specifying the `datasource` attribute. Currently supported values are `orm` and `search`.
- custom Search filter added: `\Oro\Bundle\SearchBundle\Datagrid\Filter\SearchStringFilter`.
- `\Oro\Bundle\SearchBundle\Query\Result\Item` is now compatible with the default backend datagrid templates.
- `\Oro\Bundle\SearchBundle\Datasource\SearchDatasource` can now be defined as the datasource of any datagrid (both frontend and backend).
- Datagrids having search datasource expect an indexed array of search indexes in 'from' part of datagrid configuration, as opposed to ORM format
- Introduced new interface Oro\Bundle\SearchBundle\Engine\IndexerInterface. Next methods were extracted from
  Oro\Bundle\SearchBundle\Engine\EngineInterface into this new interface: `save`, `delete`, `reindex`.
- Configuration parameter **realtime_update** and container parameter `oro_search.realtime_update` were removed. All index operations are async now.
- Oro/Bundle/SearchBundle/Entity/UpdateEntity and Oro/Bundle/SearchBundle/EventListener/UpdateSchemaDoctrineListener were removed
- `oro_search.search.engine.indexer` service was replaced with async implementation `oro_search.async.indexer`. Use sync indexer only for test environment.
- New helper trait Oro/Component/Testing/SearchExtensionTrait - easy access to sync indexer for test environment
- Removed `Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface` and classes that implement it:
- Changed constructor and replaced `Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface` with `Oro\Bundle\EntityBundle\Provider\EntityNameResolver` in classes:
  - `Oro\Bundle\SearchBundle\Engine\AbstractIndexer`
  - `Oro\Bundle\SearchBundle\Engine\OrmIndexer`
  - `Oro\Bundle\SearchBundle\EventListener\PrepareResultItemListener`
  - `Oro\Bundle\ElasticSearchBundle\Engine\ElasticSearchIndexer`
  - `Oro\Bundle\ActivityBundle\Entity\Manager\ActivityContextApiEntityManager`
  - `Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer`
  - `Oro\Bundle\ActivityBundle\Form\Type\ContextsSelectType`
  - `Oro\Bundle\CalendarBundle\Form\DataTransformer\AttendeesToViewTransformer`
- Removed (deprecated) usage of `title_fields` as they are not available on all search engines (e.g. elastic search). Entity titles will resolve using EntityNameResolver. This may affect search results (e.g. `recordTitle` and `record_string` in functional tests are changed).

#### ElasticSearchBundle
- Changed constructor of `Oro\Bundle\ElasticSearchBundle\Engine\ElasticSearchIndexer`. Replaced `EntityTitleResolverInterface` with `EntityNameResolver`.

#### ActivityBundle:
- Changed constructor of `Oro\Bundle\ActivityBundle\Autocomplete\ContextSearchHandler`. Replaced `ObjectMapper` with `EntityNameResolver`. Class now use EntityNameResolver instead of `title_fields`.
- Removed method `getActivityTargetEntities` from `Oro\Bundle\ActivityBundle\Model\ActivityInterface` and `Oro\Bundle\ActivityBundle\Model\ExtendActivity`. To avoid BC break this method is still generated, but it is marked as deprecated.
- Removed constant `GRID_EXTENDED_ENTITY_PATH` from `Oro\Bundle\ActivityBundle\Grid\Extension\ContextsExtension`
- Removed constant `GRID_FROM_PATH` from `Oro\Bundle\ActivityBundle\Grid\Extension\ContextsExtension`
- Changed signature of constructor of `Oro\Bundle\ActivityBundle\Grid\Extension\ContextsExtension`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.

#### ActivityListBundle:
- Removed method `getActivityListTargetEntities` from `Oro\Bundle\ActivityListBundle\Entity\ActivityList`. To avoid BC break this method is still generated, but it is marked as deprecated.

#### UIBundle:
- Placeholders configuration now loads from `Resources/config/oro/placeholders.yml` file instead of `Resources/config/placeholders.yml`.
- Additional common root node `placeholders` were added for placeholders configurations in `Resources/config/oro/placeholders.yml` file.
   *Please node* that your configuration now should have two `placeholders` nodes (one nested in other) instead of single one.
```YAML
placeholders:
    placeholders: ...
    items: ...
```
- Main menu dropdown active item is now triggering a page refresh, despite the Backbone router limitations.
- Upgrade Font-awesome component to 4.6.* version.
- Updated jquery.mCustomScrollbar plugin to version 3.1.5.
- Changed `form_row` block to support of form field 'hints' which allows rendering of simple help section for the respective form control.
- Updated jQuery and jQuery-UI libraries to version 3.1.* and 1.12.* accordingly.
- Updated Backbone library to version 1.2.*.
- Updated Underscore library to version 1.8.*.
- Class `Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider`
    - construction signature was changed now it takes next arguments:
        - `array` $placeholders
        - `ResolverInterface` $resolver
        - `SecurityFacade` $securityFacade
        - `FeatureChecker` $featureChecker

#### RequireJS:
- Updated RequireJS library to version 2.3.*

#### FormBundle:
- Added `Oro\Bundle\FormBundle\Form\Extension\HintFormExtension` to support hints.

#### DashboardBundle:
- Dashboards configurations now loads from `Resources/config/oro/dashboards.yml` instead of `Resources/config/dashboard.yml` file.
- Root node for dashboards configuration in `Resources/config/oro/dashboards.yml` file were changed from `oro_dashboard_config` to `dashboards`.
- Class `Oro\Bundle\DashboardBundle\Model\WidgetConfigs`
    - construction signature was changed now it takes next arguments:
        - `ConfigProvider` $configProvider,
        - `ResolverInterface` $resolver,
        - `EntityManagerInterface` $entityManager,
        - `ConfigValueProvider` $valueProvider,
        - `TranslatorInterface` $translator,
        - `EventDispatcherInterface` $eventDispatcher,
        - `WidgetConfigVisibilityFilter` $visibilityFilter
    - method `filterWidgets` signature was changed now it takes next arguments:
        - `array` $items
        - $widgetName = null
- Constructor of `Oro\Bundle\DashboardBundle\Model\Factory` was changed. Added `WidgetConfigs $widgetConfigs` as last argument.

#### NavigationBundle:
- Navigation configuration now loads form `Resources/config/oro/navigation.yml` instead of `Resources/config/navigation.yml` file.
- Configuration nodes in `Resources/config/oro/navigation.yml` were nested under single root node `navigation`.
- Configuration nodes in `Resources/config/oro/navigation.yml` were renamed:
    * `oro_titles` to `titles`
    * `oro_menu_config` to `menu_config`
    * `oro_navigation_elements` to `navigation_elements`
- All configuration nodes in `Resources/config/oro/navigation.yml` were renamed to snake case
- Added class `Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder` that implements `Oro\Bundle\NavigationBundle\Menu\BuilderInterface`.
- Added `tree.$.scope_type`, `tree.$.max_nesting_level`, `tree.$.read_only` and `items.$.read_only` nodes to `Oro\Bundle\NavigationBundle\DependencyInjection\Configuration`.
- Added interface `Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface`.
- Added trait `Oro\Bundle\NavigationBundle\Entity\MenuUpdateTrait`.
- Added entity `Oro\Bundle\NavigationBundle\Entity\MenuUpdate` that extends `Oro\Bundle\NavigationBundle\Model\ExtendMenuUpdate` and implements `Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface`.
- Added new exceptions:
    * `Oro\Bundle\NavigationBundle\Exception\MaxNestingLevelExceededException`
    * `Oro\Bundle\NavigationBundle\Exception\NotFoundParentException`
- Added class `Oro\Bundle\NavigationBundle\JsTree\MenuUpdateTreeHandler` that provides menu tree data in format used by `jstree`.
- Added class `Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager` with service `oro_navigation.manager.menu_update`.
- Added class `Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils`.
- Moved class `Oro\Bundle\NavigationBundle\Menu\FeatureAwareMenuFactoryExtension` to `Oro\Bundle\FeatureToggleBundle\Menu\FeatureAwareMenuFactoryExtension`.
- Moved class `Oro\Bundle\NavigationBundle\Event\DoctrineTagEventListener` to `Oro\Bundle\SyncBundle\Event\DoctrineTagEventListener`.
- Moved class `Oro\Bundle\NavigationBundle\Twig\ContentTagsExtension` to `Oro\Bundle\SyncBundle\Twig\ContentTagsExtension`.
- Moved class `Oro\Bundle\NavigationBundle\Content\TagGeneratorChain` to `Oro\Bundle\SyncBundle\Content\TagGeneratorChain`.
- Moved class `Oro\Bundle\NavigationBundle\Content\DoctrineTagGenerator` to `Oro\Bundle\SyncBundle\Content\DoctrineTagGenerator`.
- Moved class `Oro\Bundle\NavigationBundle\Content\SimpleTagGenerator` to `Oro\Bundle\SyncBundle\Content\SimpleTagGenerator`.
- Moved class `Oro\Bundle\NavigationBundle\Content\DataGridTagListener` to `Oro\Bundle\SyncBundle\Content\DataGridTagListener`.
- Moved class `Oro\Bundle\NavigationBundle\Content\TopicSender` to `Oro\Bundle\SyncBundle\Content\TopicSender`.
- Added class `Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuExtensionPass` compiler pass for registering menu factory extensions by tag `oro_navigation.menu_extension`.
- Moved twig template `OroNavigationBundle:Include:contentTags.html.twig` to `OroSyncBundle:Include:contentTags.html.twig`.
- Moved JS file `js/app/modules/content-manager-module.js` to `SyncBundle`.
- Moved JS file `js/content/grid-builder.js` to `SyncBundle`.
- Moved JS file `js/content-manager.js` to `SyncBundle`.
- Moved DOC file `doc/content_outdating.md` to `SyncBundle`.
- Moved DOC file `doc/mediator-handlers.md` to `SyncBundle`.
- Class `Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider`
    - construction signature was changed now it takes next arguments:
        - `FactoryInterface` $factory,
        - `ArrayLoader` $loader,
        - `MenuManipulator` $manipulator
- Added new command `oro:navigation:menu:reset` that removes changes in menus for different scopes.
- Removed class `Oro\Bundle\NavigationBundle\Title\StoredTitle`.
- Changed signature of constructor of `Oro\Bundle\NavigationBundle\Provider\TitleService`. Parameter `Serializer $serializer` was removed.
- Added new datagrid data source `Oro\Bundle\NavigationBundle\Datagrid\MenuUpdateDatasource`.
- Added new entity repository `Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository`.


#### EmailBundle
- Constructor of `Oro\Bundle\EmailBundle\Form\DataTransformer\EmailTemplateTransformer` was changed. Removed the arguments.
- Constructor of `Oro\Bundle\EmailBundle\Form\Type\EmailTemplateRichTextType` was changed. Removed the arguments.
- Constructor of `Oro\Bundle\EmailBundle\Form\Type\EmailType` was changed. Added `ConfigManager $configManager` as last argument.
- Constructor of `Oro\Bundle\EmailBundle\EventListener\EntityListener` was changed. Added `MessageProducerInterface $producer` as last argument.
- Constructor of `Oro\Bundle\EmailBundle\EventListener\AutoResponseListener` was changed. Added `MessageProducerInterface $producer` as last argument.
- Constructor of `Oro\Bundle\EmailBundle\EventListener\PrepareResultItemListener` was changed. Added `Oro\Bundle\EntityBundle\ORM\DoctrineHelper` as last argument.
- Moved class `Oro\Bundle\EmailBundle\Command\Manager\AssociationManager` to `Oro\Bundle\EmailBundle\Async\Manager`. Constructor of `Oro\Bundle\EmailBundle\Command\Manager\AssociationManager` was changed. Added `MessageProducerInterface` as last argument.
- Service name `oro_email.command.association_manager` was changed to `oro_email.async.manager.association_manager`
- `Oro/Bundle/EmailBundle/Cache/EntityCacheClearer` deprecated, tag on `oro_email.entity.cache.clearer` removed
- `oro_email.email_address.entity_manager` inherits `oro_entity.abstract_entity_manager`
- `Oro/Bundle/EmailBundle/Entity/MailboxProcessSettings` no longer inherits `Oro\Bundle\EmailBundle\Form\Model\ExtendMailboxProcessSettings`
- `Oro\Bundle\EmailBundle\Form\Model\ExtendMailboxProcessSettings` was removed
- Class `Oro\Bundle\EmailBundle\Form\Model\Email`
    - method `getContexts` now returns `Doctrine\Common\Collections\Collection` instead of array
- Constructor of `Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage` was changed. Added `FeatureChecker $featureChecker` argument.
- The command `oro:email:add-associations` (class `Oro\Bundle\EmailBundle\Command\AddAssociationCommand`) was removed. Produce message to the topic `oro.email.add_association_to_email` or `oro.email.add_association_to_emails` instead.
- The command `oro:email:autoresponse` (class `Oro\Bundle\EmailBundle\Command\AutoResponseCommand`) was removed. Produce message to the topic `oro.email.send_auto_response` or `oro.email.send_auto_responses` instead.
- The command `oro:email:flag-sync` (class `Oro\Bundle\EmailBundle\Command\EmailFlagSyncCommand`) was removed. Produce message to the topic `oro.email.sync_email_seen_flag` instead.
- The command `oro:email-attachment:purge` (class `Oro\Bundle\EmailBundle\Command\PurgeEmailAttachmentCommand`) was removed. Produce message to the topic `oro.email.purge_email_attachments` instead.
- The command `oro:email:update-email-owner-associations` (class `Oro/Bundle/EmailBundle/Command/UpdateEmailOwnerAssociationsCommand`) was removed. Produce message to the topic `oro.email.update_email_owner_association` or `oro.email.update_email_owner_associations` instead.
- Added `Oro\Bundle\EmailBundle\Form\Model\SmtpSettings` value object.
- Added `Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory` for creating value objects from the request for now.
- Added `Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker` service `oro_email.mailer.checker.smtp_settings`, used to check connection with a given `SmptSettings` value object.
- Added `Oro\Bundle\EmailBundle\Form\Handler\EmailConfigurationHandler` which triggers `Oro\Bundle\EmailBundle\Event\SmtpSettingsSaved`.
- Added `Oro\Bundle\EmailBundle\Controller\EmailController::checkSmtpConnectionAction`.
- Added `Oro\Bundle\EmailBundle\Mailer\DirectMailer::afterPrepareSmtpTransport`.
- Added `Oro\Bundle\EmailBundle\Provider\SmtpSettingsProvider` to get smtp settings from configuration.
- Added service `oro_email.command.email_body_sync` for `Oro\Bundle\EmailBundle\Command\Cron\EmailBodySyncCommand` command.

#### EntityBundle
- Added possibility to define
[entity repositories as a services](./src/Oro/Bundle/EntityBundle/Resources/doc/repositories_as_a_services.md)
by the usage of `oro_entity.abstract_repository` as a parent service
- `Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface::getName` introduced

Before
```
oro_workflow.repository.workflow_item:
    class: Doctrine\ORM\EntityRepository
    factory:  ["@oro_entity.doctrine_helper", getEntityRepository]
```

After
```
oro_workflow.repository.workflow_item:
    class: 'Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository'
    parent: oro_entity.abstract_repository
```

- `oro_entity.abstract_entity_manager` introduced. Please inherit all your doctrine entity manager factory services

Before
```
oro_email.email_address.entity_manager:
    public: false
    class: Doctrine\ORM\EntityManager
    factory: ['@doctrine', getManagerForClass]
```

After
```
oro_email.email_address.entity_manager:
    parent: oro_entity.abstract_entity_manager
```

- Added entity fallback functionality
- Added EntityFieldFallbackValue entity to store fallback information
- Added EntityFallbackResolver service which handles fallback resolution
- Added SystemConfigFallbackProvider service which handles `systemConfig` fallback type
- Added EntityFallbackExtension service which reads fallback values of entities in twig
- Added AbstractEntityFallbackProvider abstract service to ease adding new fallback types, please refer
to the [Fallback documentation](./src/Oro/Bundle/EntityBundle/Resources/doc/entity_fallback.md) for details
- `Oro\Bundle\EntityBundle\Provider\EntityNameProvider` now is the generic Entity Name Provider which resolves:
   - 'Short' format: title based on entity fields from 'firstName', 'name', 'title', 'subject' (uses only the first that is found)
   - 'Full' format: a space-delimited concatenation of all string fields of the entity.
   - For both formats: will return the entity ID when fields are found but their value is empty. Same applies for both `getName` and `getNameDQL` methods. Will return `false` if no suitable fields are available.
- Added `Oro\Bundle\EntityBundle\Provider\FallbackEntityNameProvider` which will resolve entity title in form of 'Item #1' (translates `oro.entity.item`). Can use only single-column identifiers, else returns `false`. Should be kept as last provider.
- Removed constant `PATH_FROM` from `Oro\Bundle\EntityBundle\Grid\CustomEntityDatagrid`

#### ContactBundle

- `Oro\Bundle\ContactBundle\Provider\ContactEntityNameProvider` now uses phone and email as fallback when entity names are empty

#### CacheBundle
- `Oro\Bundle\CacheBundle\Manager\OroDataCacheManager` now has method `clear` to clear cache at all cache providers

#### MigrationBundle
- `Oro\Bundle\MigrationBundle\Migration\MigrationExecutor` now clears cache at all cache providers after successful migration load

#### FeatureToggleBundle
- Added class `Oro\Bundle\FeatureToggleBundle\Menu\FeatureAwareMenuFactoryExtension` moved from `NavigationBundle`.

#### SyncBundle
- Added class `Oro\Bundle\SyncBundle\DependencyInjection\Compiler\SkipTagTrackingPass` compiler pass that add skipped entity classes to `oro_sync.event_listener.doctrine_tag` service.
- Added class `Oro\Bundle\SyncBundle\Event\DoctrineTagEventListener` moved from `NavigationBundle`.
- Added class `Oro\Bundle\SyncBundle\Twig\ContentTagsExtension` moved from `NavigationBundle`.
- Added class `Oro\Bundle\SyncBundle\Content\TagGeneratorChain` moved from `NavigationBundle`.
- Added class `Oro\Bundle\SyncBundle\Content\DoctrineTagGenerator` moved from `NavigationBundle`.
- Added class `Oro\Bundle\SyncBundle\Content\SimpleTagGenerator` moved from `NavigationBundle`.
- Added class `Oro\Bundle\SyncBundle\Content\DataGridTagListener` moved from `NavigationBundle`.
- Added class `Oro\Bundle\SyncBundle\Content\TopicSender` moved from `NavigationBundle`.
- Added twig template `OroSyncBundle:Include:contentTags.html.twig` moved from `NavigationBundle`.
- Added JS file `js/app/modules/content-manager-module.js` moved from `NavigationBundle`.
- Added JS file `js/content/grid-builder.js` moved from `NavigationBundle`.
- Added JS file `js/content-manager.js` moved from `NavigationBundle`.
- Added DOC file `doc/content_outdating.md` moved from `NavigationBundle`.
- Added DOC file `doc/mediator-handlers.md` moved from `NavigationBundle`.

#### DependencyInjection Component
- Added trait `Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait`

#### EntitySerializer Component
- Changed signature of `transform` method of `Oro\Component\EntitySerializer\DataTransformerInterface`. Added `array $context` as the last parameter.
- Changed signature of `post_serialize` callbacks for the EntitySerializer. Added `array $context` as the last parameter.
- Changed signature of `post_serialize` callbacks for the EntitySerializer. Added `array $context` as the last parameter.
- Changed signature of `serialize` method of `Oro\Component\EntitySerializer\EntitySerializer`. Added `array $context = []` as the last parameter.
- Changed signature of `serializeEntities` method of `Oro\Component\EntitySerializer\EntitySerializer`. Added `array $context = []` as the last parameter.

#### NotificationBundle
- Moved interface `Oro\Bundle\NotificationBundle\Processor\EmailNotificationInterface` to `Oro\Bundle\NotificationBundle\Model` namespace
- Moved interface `Oro\Bundle\NotificationBundle\Processor\SenderAwareEmailNotificationInterface` to `Oro\Bundle\NotificationBundle\Model` namespace
- Removed class `Oro\Bundle\NotificationBundle\Processor\AbstractNotificationProcessor`
- Removed service @oro_notifications.manager.email_notification and its class `Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor` as now the email notifications are processed asynchronously with `Oro\Bundle\NotificationBundle\Async\SendEmailMessageProcessor`
- Added class `Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager`; some logic from `Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor` was moved there
- Added class `Oro\Bundle\NotificationBundle\Manager\EmailNotificationSender`; some logic from `Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor` was moved there
- Added class `Oro\Bundle\NotificationBundle\Async\Topics`
- Added class `Oro\Bundle\NotificationBundle\Async\SendEmailMessageProcessor`
- Constructor of `Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler` was changed: the first argument type is `Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager` instead of `Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor`
- Constructor of `Oro\Bundle\NotificationBundle\Model\MassNotificationSender` was changed: the first argument type is `Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager` instead of `Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor`

#### CalendarBundle
- CalendarBundle moved to a separate package

#### ReminderBundle
- Constructor of `Oro\Bundle\ReminderBundle\Model\Email\EmailSendProcessor` was changed: the first argument type is `Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager` instead of `Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor`

#### DataAuditBundle
- `Oro\Bundle\DataAuditBundle\Loggable\LoggableManager` was removed. Some logic moved to `Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener` class and some backend processors.
- `Oro\Bundle\DataAuditBundle\EventListener\EntityListener` was removed. Similar logic could be found in `Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener` class.
- `Oro\Bundle\DataAuditBundle\EventListener\KernelListener` was removed.
- `Oro\Bundle\DataAuditBundle\Metadata\Driver\AnnotationDriver` was removed.
- `Oro\Bundle\DataAuditBundle\Metadata\ExtendMetadataFactory` was removed.
- `Loggable` and `Versioned` annotations were removed. Use entity config auditable option instead.
- `Oro\Bundle\DataAuditBundle\EventListener\AuditGridListener` was removed. Similar functionality can be found in `Oro\Bundle\DataAuditBundle\Datagrid\EntityTypeProvider`.
- `Oro\Bundle\DataAuditBundle\Loggable\AuditEntityMapper` was renamed to `Oro\Bundle\DataAuditBundle\Provider\AuditEntityMapper`.

#### ImapBundle
 - The command `oro:imap:clear-mailbox` was removed. Produce message to the topic `Oro\Bundle\ImapBundle\Async\Topics::CLEAR_INACTIVE_MAILBOX` instead.
 - Added service `oro_imap.command.email_sync` for `Oro\Bundle\ImapBundle\Command\Cron\EmailSyncCommand` command.

#### CronBundle
- Removed class `Oro\Bundle\CronBundle\Action\CreateJobAction`, service `oro_cron.action.create_job` and action `@create_job`
- Removed class `Oro\Bundle\CronBundle\Controller\JobController`.
- Removed class `Oro\Bundle\CronBundle\DependencyInjection\Compiler\JobSerializerMetadataPass`.
- Removed class `Oro\Bundle\CronBundle\DependencyInjection\Compiler\JobStatisticParameterPass`.
- Removed class `Oro\Bundle\CronBundle\Entity\Manager\JobManager` and service `oro_cron.job_manager`.
- Removed class `Oro\Bundle\CronBundle\Entity\Repository\JobRepository`.
- Removed class `Oro\Bundle\CronBundle\Job\Daemon` and service `oro_cron.job_daemon`
- Removed class `Oro\Bundle\CronBundle\JobQueue\JMSJobQueueBundle`
- Added command `oro:cron:definitions:load` (class `Oro\Bundle\CronBundle\Command\CronDefinitionsLoadCommand`) to load cron command definitions to schedule table
- Temporary added listener `Oro\Bundle\CronBundle\Migrations\Schema\v2_0\SchemaColumnDefinitionListener` to prevent default behavior for `jms_job_safe_object` type.
- Removed command `oro:cron:cleanup` (class `Oro\Bundle\CronBundle\Command\CleanupCommand`).
- Removed command `oro:daemon` (class `Oro\Bundle\CronBundle\Command\DaemonMonitorCommand`).
- Removed command `oro:jms-job-queue:count` (class `Oro\Bundle\CronBundle\Command\JmsJobCountCommand`).
- Command `oro:cron` (class `Oro\Bundle\CronBundle\Command\CronCommand`)  doesn't have option `skipCheckDaemon` any more.
- Parameters `max_concurrent_jobs`, `max_runtime`, `jms_statistics` under `oro_cron` root were removed.
- Removed listener `Oro\Bundle\CronBundle\EventListener\JobSubscriber`
- Removed listener `Oro\Bundle\CronBundle\EventListener\LoadClassMetadataSubscriber` and service `oro_cron.listener.load_class_metadata_subscriber`

#### UserBundle
- Added `auth_status` extended enum property to `Oro\Bundle\UserBundle\Entity\User` entity.
- Added `Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity` to User model.
- User password requirements are more restrictive by default and require 8 characters, an upper case letter, and a number.
- Any new users or changing of existing passwords need to meet the password requirements specified in System Configuration/General Setup/User Settings. Existing user passwords are not affected.
- Removed service @oro_user.password_reset.widget_provider.actions (replaced by @oro_user.forced_password_reset.widget_provider.actions)
- Constructor of `Oro\Bundle\UserBundle\Entity\UserManager` changed. Added 4-th parameter of type `Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider`.
- Added method `setAuthStatus($user, $enumId)` to `Oro\Bundle\UserBundle\Entity\UserManager` method to set `auth_status` of a User by enum id.
- Removed `Oro\Bundle\UserBundle\Security\WsseAuthListener` class.

#### ImapBundle
- The command `oro:imap:clear-mailbox` was removed. Produce message to the topic `oro.imap.clear_inactive_mailbox` instead.
- Removed action `@job_add_dependency`
- Changed property name from `$possibleSentFolderNameMap` to `$knownFolderNameMap` in `Oro\Bundle\ImapBundle\Mail\Storage\Folder`
- Changed method name from `guessSentTypeByName` to `guessFolderTypeByName` in `Oro\Bundle\ImapBundle\Mail\Storage\Folder`

#### OroInstallerBundle
- Added interface `Oro\Bundle\InstallerBundle\CacheWarmer\NamespaceMigrationProviderInterface`. it makes available add the rules for command "oro:platform:upgrade20"

#### CurrencyBundle
- `getViewType` method was removed form `Oro\Bundle\CurrencyBundle\Config\CurrencyConfigInterface`
- `VIEW_TYPE_SYMBOL` and `VIEW_TYPE_ISO_CODE` constants were removed from `Oro\Bundle\CurrencyBundle\Config\CurrencyConfigInterface`
- `Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface` was renamed to `CurrencyListProviderInterface`
- `Oro\Bundle\CurrencyBundle\Provider\DefaultCurrencyProviderInterface` was added
- `Oro\Bundle\CurrencyBundle\Config\CurrencyConfigInterface` was renamed to `Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface`
- `Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface` extends `Oro\Bundle\CurrencyBundle\Provider\CurrencyListProviderInterface` and `Oro\Bundle\CurrencyBundle\Provider\DefaultCurrencyProviderInterface`
- `Oro\Bundle\CurrencyBundle\Config\CurrencyConfigManager` was renamed to `DefaultCurrencyConfigProvider`
- Changed signature of constructor of `Oro\Bundle\CurrencyBundle\Datagrid\EventListener\ColumnConfigListener`. The argument `EntityClassResolver $entityClassResolver` was removed.

#### OroTrackingBundle
- Moved ``TrackingBundle`` to a separate ``marketing`` package, required by default in the CRM applications.
- Deleted ``tracking.php`` front controllers from applications. This file is created in application's `/web` folder automatically duting an instalation.

#### OroNoteBundle
- Implementation of activity list relation with entity  `Oro\Bundle\NoteBundle\Entity\Note` was changed. Now the entity is a regular activity entity like others: Email, Task, Call, Email, etc.

Before
- One Note could be related only to one entity in the Activity List.

After
- One Note could be related to many entities in the Activity List. Context field can be used to add Note to multiple entities.

- Removed property `entityId` from SOAP API for entity `Oro\Bundle\NoteBundle\Entity\Note`.
- Added use of `Oro\Bundle\ActivityBundle\Model\ActivityInterface` into class `Oro\Bundle\NoteBundle\Entity\Note`.
- Removed methods from entity `Oro\Bundle\NoteBundle\Entity\Note`: `supportTarget`, `getTarget`, `setTarget`. Methods of `Oro\Bundle\ActivityBundle\Model\ActivityInterface` should be used to access target entities instead.
- Removed extra classes and services were as unnecessary after Note entity became a regular activity entity. See detailed list of removed items below.
- Removed class `Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension`. Generic extension `Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension` is used to add relation of entity with Note as Activity.
- Removed class `Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface`. Generic interface `\Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface` should be used instead in schema migrations.
- Removed entity config with scope "note" after Note entity became a regular Activity entity.
- Removed class `Oro\Bundle\NoteBundle\Placeholder\PlaceholderFilter` and service `oro_note.placeholder.filter`.
- Removed class `Oro\Bundle\NoteBundle\Provider\NoteExclusionProvider` and service `oro_note.exclusion_provider`.
- Removed class `Oro\Bundle\NoteBundle\Tools\NoteAssociationHelper` and service `oro_note.association_helper`.
- Removed class `Oro\Bundle\NoteBundle\Tools\NoteEntityConfigDumperExtension` and service `oro_note.entity_config_dumper.extension`.
- Removed class `Oro\Bundle\NoteBundle\Tools\NoteEntityGeneratorExtension` and service `oro_note.entity_generator.extension`.
- Removed class `Oro\Bundle\NoteBundle\EventListener\MergeListener` and service `oro_note.listener.merge_listener`. Generic class `Oro\Bundle\ActivityListBundle\EventListener\MergeListener` applicable for activity entities now is used instead.
- Removed class `Oro\Bundle\NoteBundle\Model\MergeModes`. Generic class `Oro\Bundle\ActivityListBundle\Model\MergeModes` applicable for activity entities now is used instead.
- Removed class `Oro\Bundle\NoteBundle\Model\Strategy\ReplaceStrategy` and service `oro_note.strategy.replace`. Generic class `Oro\Bundle\ActivityListBundle\Model\Strategy\ReplaceStrategy` applicable for activity entities now is used instead.
- Removed class `Oro\Bundle\NoteBundle\Model\Stratgy\UniteStrategy` and service `oro_note.strategy.unite`. Generic class `Oro\Bundle\ActivityListBundle\Model\Strategy\UniteStrategy` applicable for activity entities now is used instead.
- Removed service `oro_note.widget_provider.actions`.
- Added parameter `renderContexts` to route controller action `Oro\Bundle\NoteBundle\Controller\Note::infoAction` (route `oro_note_widget_info`). Default value of the parameter is `true`.
- Changed signature of controller action `Oro\Bundle\NoteBundle\Controller\Note::createAction`. The parameters of route `oro_note_create` remain the same as before - `entityClass` and `entityId`.
- Changed signature of method `Oro\Bundle\NoteBundle\Form\Handler\NoteHandler::__construct`.
- Changed signature of method `Oro\Bundle\NoteBundle\Provider\NoteActivityListProvider::__construct`.
- Replaced method `Oro\Bundle\NoteBundle\Form\Type\NoteType::setDefaultOptions` with `Oro\Bundle\NoteBundle\Form\Type\NoteType::configureOptions`.
- Changed view template `OroNoteBundle:Note:js/activityItemTemplate.html.twig`.
- Changed view template `OroNoteBundle:Note:widget/info.html.twig`.
- Removed parameter `oro_note.manager.api.class` from DIC.
- Removed parameter `oro_note.activity_list.provider.class` from DIC.
- Removed parameter `oro_note.manager.class` from DIC.

#### TagBundle
- Constructor of `Oro\Bundle\TagBundle\Grid\Extension\TagSearchResultsExtension` was changed. Dependency on `Doctrine\ORM\EntityManager` was removed.
- Removed constant `GRID_FROM_PATH` from `Oro\Bundle\TagBundle\Grid\AbstractTagsExtension`
- Removed constant `GRID_COLUMN_ALIAS_PATH` from `Oro\Bundle\TagBundle\Grid\AbstractTagsExtension`
- Changed signature of constructor of `Oro\Bundle\TagBundle\Grid\AbstractTagsExtension`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.
- Changed signature of constructor of `Oro\Bundle\TagBundle\Grid\TagsExtension`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.
- Changed signature of constructor of `Oro\Bundle\TagBundle\Grid\TagsReportExtension`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.
