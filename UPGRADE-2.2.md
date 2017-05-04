UPGRADE FROM 2.1 to 2.2
=======================

ActionBundle
------------
- Class `Oro\Bundle\DataGridBundle\Extension\Action\Listener\ButtonsListener`:
    - renamed to `Oro\Bundle\ActionBundle\Datagrid\Provider\DatagridActionButtonProvider`
    - refactored to implement `Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface`
    - removed class property `protected $searchContext = []`
    - changed signature of method `protected function getRowConfigurationClosure(DatagridConfiguration $configuration, ButtonSearchContext $context)`
    - added second argument `ButtonSearchContext $context` to method `protected function applyActionsConfig()`
    - added second argument `ButtonSearchContext $context` to method `protected function processMassActionsConfig()`
    - added dependency on `Symfony\Component\EventDispatcher\EventDispatcherInterface` that should be injected to service by `public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)`
- Service `oro_action.datagrid.event_listener.button` now has name `oro_action.datagrid.action.button_provider` and registered through the tag `oro_datagrid.extension.action.provider`
- Added `Oro\Bundle\ActionBundle\Model\AbstractGuesser`:
    - defined as abstract service `oro_action.abstract_guesser` with arguments `@form.registry, @doctrine, @oro_entity_config.provider.entity, @oro_entity_config.provider.form`
    - added constructor with arguments `FormRegistry $formRegistry`, `ManagerRegistry $managerRegistry`, `ConfigProvider $entityConfigProvider`, `ConfigProvider $formConfigProvider`
    - extracted methods from `Oro\Bundle\ActionBundle\Model\AttributeGuesser`:
        - `addDoctrineTypeMapping` with arguments: `$doctrineType, $attributeType, array $attributeOptions = []`
        - `addFormTypeMapping with` with arguments: `$variableType, $formType, array $formOptions = []`
        - `guessMetadataAndField` with arguments: `$rootClass, $propertyPath`
        - `guessParameters` with arguments: `$rootClass, $propertyPath`
        - `setDoctrineTypeMappingProvider` with argument: `DoctrineTypeMappingProvider $doctrineTypeMappingProvider = null`
- Class `Oro\Bundle\ActionBundle\Model\AttributeGuesser`:
    - now extends `Oro\Bundle\ActionBundle\Model\AbstractGuesser`
    - service `oro_action.attribute_guesser` has parent defined as `oro_action.abstract_guesser`
- Class `Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension` (`oro_action.provider.button.extension.operation`):
    - changed constructor signature:
        - added `OptionsResolver $optionsResolver`;
        - removed `OptionsAssembler $optionsAssembler`;
        - removed `ContextAccessor $contextAccessor`;

ActivityBundle
--------------
- Class `Oro\Bundle\ActivityBundle\Provider\ContextGridProvider` was removed
- Class `Oro\Bundle\ActivityBundle\Controller\ActivityController`
    - action `contextAction` is rendered in `OroDataGridBundle:Grid/dialog:multi.html.twig`
    - action `contextGridAction` was removed
    
ConfigBundle
--------------
- Class `Oro\Bundle\ConfigBundle\Config\AbstractScopeManager`:
    - added third argument `EventDispatcherInterface $eventDispatcher`
    - abstract service `oro_config.scope_manager.abstract` now has third argument defined as `@event_dispatcher`
- Class `ConfigManagerScopeIdUpdateEvent` was added

CurrencyBundle
--------------
- Interface `Oro\Bundle\MultiCurrencyBundle\Query\CurrencyQueryBuilderTransformerInterface`:
    - added method `getTransformSelectQueryForDataGrid` that allow to use query transformer in datagrid config


DataAuditBundle
---------------
A new string field `ownerDescription` with the database column `owner_description` was added to the entity 
`Oro\Bundle\DataAuditBundle\Entity\Audit` and to the base class `Oro\Bundle\DataAuditBundle\Entity\AbstractAudit`

ApiBundle
---------
- Added class `Oro\Bundle\ApiBundle\Processor\ApiFormBuilderSubscriberProcessor`
    - can be used to add subscribers to `FormContext`

DataGridBundle
--------------
- Interface `Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface` added.
- Tag `oro_datagrid.extension.action.provider` added. To be able to register by `DatagridActionProviderInterface` any datagrid action configuration providers.
- Class `Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension` (`@oro_datagrid.extension.action`) fourth `__construct` argument (`Symfony\Component\EventDispatcher\EventDispatcherInterface`) were removed.
- Removed event `oro_datagrid.datagrid.extension.action.configure-actions.before`, now it is a call of `Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface::hasActions` of registered through a `oro_datagrid.extension.action.provider` tag services.
- Interface `Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface`
    - the signature of method `getDatagrid` was changed - added new parameter `array $additionalParameters = []`.

- Added abstract entity class `Oro\Bundle\DataGridBundle\Entity\AbstractGridView`
    - entity `Oro\Bundle\DataGridBundle\Entity\GridView` extends from it
- Added abstract entity class `Oro\Bundle\DataGridBundle\Entity\AbstractGridViewUser`
    - entity `Oro\Bundle\DataGridBundle\Entity\GridViewUser` extends from it
- Class `Oro\Bundle\DataGridBundle\Controller\Api\Rest\GridViewController`
    - added argument `Request $request` for methods:
        - `public function postAction(Request $request)`
        - `public function putAction(Request $request, $id)`
    - changed type hint of first argument of method `checkEditPublicAccess()` from `GridView $gridView` to `AbstractGridView $gridView`
- Changed type hint for first argument of `Oro\Bundle\DataGridBundle\Entity\Manager\GridViewApiEntityManager::setDefaultGridView()` from `User $user` to `AbstractUser $user`
- Class `Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager`
    - changed type hint for:
        - first argument of method `public funtion setDefaultGridView()` from `User $user` to `AbstractUser $user`
        - second argument of method `protected function isViewDefault()` from `User $user` to `AbstractUser $user`
        - first argument of method `public funtion getAllGridViews()` from `User $user` to `AbstractUser $user`
        - first argument of method `public funtion getDefaultView()` from `User $user` to `AbstractUser $user`
- Class `Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository`
    - changed type hint for third argument of method `public funtion findDefaultGridViews()` from `GridView $gridView` to `AbstractGridView $gridView`
- Class `Oro\Bundle\DataGridBundle\Entity\Repository\GridViewUserRepository`
    - added method `findByGridViewAndUser(AbstractGridView $view, UserInterface $user)`
- Class `Oro\Bundle\DataGridBundle\Form\Handler\GridViewApiHandler`
    - changed type hint for:
        - first argument of method `protected funtion onSuccess()` from `GridView $entity` to `AbstractGridView $entity`
        - first argument of method `protected funtion setDefaultGridView()` from `GridView $entity` to `AbstractGridView $entity`
        - first argument of method `protected funtion fixFilters()` from `GridView $entity` to `AbstractGridView $entity`
- Class `Oro\Bundle\DataGridBundle\Async\Export\PreExportMessageProcessor` now extends `Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessorAbstract` instead of implementing `ExportMessageProcessorAbstract` and `TopicSubscriberInterface`. Service calls `setExportHandler` with `@oro_datagrid.handler.export` and `setExportIdFetcher` with `@oro_datagrid.importexport.export_id_fetcher` were added. The constructor was removed, the parent class constructor is used.
- Class `Oro\Bundle\DataGridBundle\Async\Export\ExportMessageProcessor` now extends `Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessorAbstract` instead of implementing `ExportMessageProcessorAbstract` and `TopicSubscriberInterface`. Service calls `setExportHandler` with `@oro_datagrid.handler.export`, `setExportConnector` with `@oro_datagrid.importexport.export_connector`, `setExportProcessor` with `@oro_datagrid.importexport.processor.export` and `setWriterChain`  with `@oro_importexport.writer.writer_chain` were added. The constructor was removed, the parent class constructor is used.
- Class `Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher`
    - the signature of `getDatagridQuery` method was changed, added parameter `string $objectIdentifier = null`

TestFrameworkBundle
-------------------
- added fourth (boolean) parameter to `\Oro\Bundle\TestFrameworkBundle\Test\WebTestCase::runCommand` `$exceptionOnError` to throw `\RuntimeException` when command should executes as utility one.

ImportExportBundle
------------------
- Message topics `oro.importexport.cli_import`, `oro.importexport.import_http_validation`, `oro.importexport.import_http` with the constants were removed.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\PreImportMessageProcessorAbstract` was added,
    - construction signature: 
        - JobRunner $jobRunner,
        - MessageProducerInterface $producer,
        - LoggerInterface $logger,
        - DependentJobService $dependentJob,
        - FileManager $fileManager,
        - AbstractImportHandler $importHandler,
        - WriterChain $writerChain,
        - $batchSize
- Class `Oro\Bundle\ImportExportBundle\Async\Import\PreCliImportMessageProcessor` now extends `Oro\Bundle\ImportExportBundle\Async\Import\PreImportMessageProcessorAbstract` instead of implementing `ExportMessageProcessorAbstract` and `TopicSubscriberInterface`. The constructor was removed, the parent class constructor is used. 
- Class `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` now extends `Oro\Bundle\ImportExportBundle\Async\Import\PreImportMessageProcessorAbstract` instead of implementing `ExportMessageProcessorAbstract` and `TopicSubscriberInterface`.  The constructor was removed, the parent class constructor is used. 
- Added class `Oro\Bundle\ImportExportBundle\Async\Import\ImportMessageProcessor`
    - construction signature: 
        - JobRunner $jobRunner,
        - ImportExportResultSummarizer $importExportResultSummarizer,
        - JobStorage $jobStorage
        - LoggerInterface $logger,
        - FileManager $fileManager,
        - AbstractImportHandler $importHandler
- Class `Oro\Bundle\ImportExportBundle\Async\Import\CliImportMessageProcessor`
    - construction signature was changed now it takes next arguments: 
        - JobRunner $jobRunner,
        - ImportExportResultSummarizer $importExportResultSummarizer,
        - JobStorage $jobStorage
        - LoggerInterface $logger,
        - FileManager $fileManager,
        - CliImportHandler $cliImportHandler
    - does not implement TopicSubscriberInterface now.
    - subscribed topic moved to tag in `mq_processor.yml`.  
    - service `oro_importexport.async.http_import` decorates `oro_importexport.async.import`
- Class `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`
    - construction signature was changed now it takes next arguments: 
        - JobRunner $jobRunner,
        - ImportExportResultSummarizer $importExportResultSummarizer,
        - JobStorage $jobStorage
        - LoggerInterface $logger,
        - FileManager $fileManager,
        - HttpImportHandler $cliImportHandler
        - TokenSerializerInterface $tokenSerializer
        - TokenStorageInterface $tokenStorage
    - does not implement TopicSubscriberInterface now.
    - subscribed topic moved to tag in `mq_processor.yml`.  
    - service `oro_importexport.async.cli_import` decorates `oro_importexport.async.import`
- Class `Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessorAbstract` that implements `MessageProcessorInterface` and `TopicSubscriberInterface` was added. 
    - construction signature:
        - JobRunner $jobRunner,
        - JobStorage $jobStorage,
        - TokenStorageInterface $tokenStorage,
        - TokenSerializerInterface $tokenSerializer,
        - LoggerInterface $logger
- Class `Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessorAbstract` that implements `MessageProcessorInterface` and `TopicSubscriberInterface` was added. 
    - construction signature:
        - JobRunner $jobRunner,
        - MessageProducerInterface $producer,
        - TokenSerializerInterface $tokenSerializer,
        - TokenStorageInterface $tokenStorage,
        - DependentJobService $dependentJob,
        - LoggerInterface $logger,
        - $sizeOfBatch
- Class `Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessor` now extends `Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessorAbstract` instead of implementing `ExportMessageProcessorAbstract` and `TopicSubscriberInterface`. Service calls `setExportHandler` with `@oro_importexport.handler.export` and `setDoctrineHelper` with `@oro_entity.doctrine_helper` were added. The constructor was removed, the parent class constructor is used. 
- Class `Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor` now extends `Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessorAbstract` instead of implementing `ExportMessageProcessorAbstract` and `TopicSubscriberInterface`. Service calls `setExportHandler` with `@oro_importexport.handler.export` and `setDoctrineHelper` with `@oro_entity.doctrine_helper` were added.  The constructor was removed, the parent class constructor is used. 


InstallerBundle
---------------
- The option `--force` was removed from `oro:install` cli command.
- Class `Oro\Bundle\InstallerBundle\Command\InstallCommand`
    - Signature of `prepareStep` method was changed, removed parameter `CommandExecutor $commandExecutor`.


IntegrationBundle
-----------------
- Class `Oro\Bundle\IntegrationBundle\Async\ReversSyncIntegrationProcessor`
    - construction signature was changed now it takes next arguments:
        - `DoctrineHelper` $doctrineHelper,
        - `ReverseSyncProcessor` $reverseSyncProcessor,
        - `TypesRegistry` $typesRegistry,
        - `JobRunner` $jobRunner,
        - `TokenStorageInterface` $tokenStorage,
        - `LoggerInterface` $logger


NavigationBundle
--------------
- Methods in class `Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader` were removed:
    - `setRouter`
    - `setCache`
- Signature of class `Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader` was changed:
    - use `Doctrine\Common\Annotations\Reader` as first argument instead of `Symfony\Component\HttpFoundation\RequestStack`
    - use `Symfony\Component\Routing\Router` as second argument
    - use `Doctrine\Common\Cache\Cache` as third argument
- Methods in class `Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType` were removed:
    - `setReaderRegistry`
    - `setTitleTranslator`
    - `setTitleServiceLink`
- Signature of class `Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType` was changed:
    - use `Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry` as second argument instead of `Doctrine\Common\Persistence\ManagerRegistry`
    - use `Oro\Bundle\NavigationBundle\Provider\TitleTranslator` as third argument instead of `Symfony\Component\Translation\TranslatorInterface`
    - use `Oro\Component\DependencyInjection\ServiceLink` as fourth argument


TestFrameworkBundle
-------------------
- added fourth (boolean) parameter to `\Oro\Bundle\TestFrameworkBundle\Test\WebTestCase::runCommand` `$exceptionOnError` to throw `\RuntimeException` when command should executes as utility one.  
 
        
WorkflowBundle
--------------
- Changed implemented interface of  `Oro\Bundle\WorkflowBundle\Model\Variable` class from `Oro\Bundle\ActionBundle\Model\ParameterInterface` to `Oro\Bundle\ActionBundle\Model\EntityParameterInterface`
- Class `Oro\Bundle\WorkflowBundle\Model\VariableGuesser`:
    - removed constructor
    - now extends `Oro\Bundle\ActionBundle\Model\AbstractGuesser`
    - service `oro_workflow.variable_guesser` has parent defined as `oro_action.abstract_guesser`
- Class `\Oro\Bundle\WorkflowBundle\EventListener\WorkflowStartListener` added
- Class `\Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemListener` auto start workflow part were moved into `\Oro\Bundle\WorkflowBundle\EventListener\WorkflowStartListener`
- Added parameter `$activeOnly` (boolean) with default `false` to method `\Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository::getAllRelatedEntityClasses`
- Class `\Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache` added:
    - **purpose**: to check whether an entity has been involved as some workflow related entity in cached manner to avoid DB calls
    - **methods**:
        - `hasRelatedActiveWorkflows($entity)`
        - `hasRelatedWorkflows($entity)`
    - invalidation of cache occurs on workflow changes events: 
        - `oro.workflow.after_update`
        - `oro.workflow.after_create`
        - `oro.workflow.after_delete`
        - `oro.workflow.activated`
        - `oro.workflow.deactivated`
- Service `oro_workflow.cache` added with standard `\Doctrine\Common\Cache\Cache` interface under namespace `oro_workflow`
- Class `Oro\Bundle\WorkflowBundle\Autocomplete\WorkflowReplacementSearchHandler` was removed
- Class `Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementSelectType` renamed to `Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementType`
- Class `Oro\Bundle\WorkflowBundle\Model\Transition`:
    - changed constructor signature:
        - added `TransitionOptionsResolver $optionsResolver`;
- Class `Oro\Bundle\WorkflowBundle\Model\TransitionAssembler` (`oro_workflow.transition_assembler`):
    - changed constructor signature:
        - added `TransitionOptionsResolver $optionsResolver`;
- Class `Oro\Bundle\WorkflowBundle\Form\Handler\TransitionCustomFormHandler` and service `@oro_workflow.handler.transition.form.page_form` removed (see `Oro\Bundle\WorkflowBundle\Processor\Transition\CustomFormProcessor`)
- Class `Oro\Bundle\WorkflowBundle\Form\Handler\TransitionFormHandler` and service `@oro_workflow.handler.transition.form` removed see replacements:
  - `Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFormProcessor`
  - `Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFormStartHandleProcessor`
- Interface `Oro\Bundle\WorkflowBundle\Form\Handler\TransitionFormHandlerInterface` removed
- Class `Oro\Bundle\WorkflowBundle\Handler\Helper\TransitionHelper` and service `@oro_workflow.handler.transition_helper` removed (see `Oro\Bundle\WorkflowBundle\Processor\Transition\Template\FormSubmitTemplateResponseProcessor`)
- Class `Oro\Bundle\WorkflowBundle\Handler\StartTransitionHandler` and service `@oro_workflow.handler.start_transition_handler` removed (see `Oro\Bundle\WorkflowBundle\Processor\Transition\StartHandleProcessor`)
- Class `Oro\Bundle\WorkflowBundle\Handler\TransitionHandler` and service `@oro_workflow.handler.transition_handler` removed (see `Oro\Bundle\WorkflowBundle\Processor\Transition\TransitionHandleProcessor`)
- Class `Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper`:
  - Constant `Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper::DEFAULT_TRANSITION_TEMPLATE` moved into `Oro\Bundle\WorkflowBundle\Processor\Transition\Template\DefaultFormTemplateResponseProcessor::DEFAULT_TRANSITION_TEMPLATE`
  - Constant `Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper::DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE` moved into `Oro\Bundle\WorkflowBundle\Processor\Transition\Template\CustomFormTemplateResponseProcessor::DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE`
  - Signature parameters **removed**:
     - `Symfony\Component\Form\FormFactoryInterface` $formFactory
     - `Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer` $workflowDataSerializer
  - Removed methods:
    - `getEntityManager` - unused
    - `getTransitionForm` - managed by processors
    - `getTransitionFormTemplate` - managed by processors
    - `processWorkflowData` - managed by processors
- Class `Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider`:
    - changed constructor signature:
        - removed `WorkflowRegistry $workflowRegistry`;
        - added `Cache $entitiesWithWorkflowsCache`;
- Added processor tag `oro_workflow.processor` and `oro_workflow.processor_bag` service to collect processors.


PlatformBundle
--------------
- Service `jms_serializer.link` was removed.
- Class `Oro\Bundle\PlatformBundle\Twig\SerializerExtension`
    - construction signature was changed, now it takes `ContainerInterface` $container instead of `ServiceLink` $serializerLink (jms_serializer.link)

EmailBundle
------------
- Class `Oro\Bundle\EmailBundle\Entity\AutoResponseRule`
    - methods related to `conditions` property were removed. Use methods related to `definition` property instead.
- Class `Oro\Bundle\EmailBundle\Entity\AutoResponseRuleCondition` was removed
- Class `Oro\Bundle\EmailBundle\Form\Type\AutoResponseRuleConditionType` was removed
- Class `Oro\Bundle\EmailBundle\Form\Type\AutoResponseRuleType`
    - form field `conditions` was removed. Use field `definition` instead.
- Class `Oro\Bundle\EmailBundle\Manager\AutoResponseManager`
    - construction signature was changed now it takes next arguments:
        - `Registry` $registry,
        - `EmailModelBuilder` $emailBuilder,
        - `Processor` $emailProcessor,
        - `EmailRenderer` $emailRender,
        - `LoggerInterface` $logger,
        - `TranslatorInterface` $translator,
        - $defaultLocale
- Class `Oro\Bundle\EmailBundle\Validator\AutoResponseRuleConditionValidator` was removed
- Class `Oro\Bundle\EmailBundle\Validator\Constraints\AutoResponseRuleCondition` was removed
- Class `Oro\Bundle\EmailBundle\Controller\AutoResponseRuleController`
    - action `update` now returns following data: `form`, `saved`, `data`, `metadata`
- template `Resources/views/Form/autoresponseFields.html.twig` was removed as it contained possibility to add collection item after arbitrary item, which is not needed anymore with new form
- template `Resources/views/AutoResponseRule/dialog/update.html.twig` was changed
- template `Resources/views/Configuration/Mailbox/update.html.twig` was changed
- template `EmailBundle/Resources/views/Form/fields.html.twig` was changed

TranslationBundle
-----------------
- Signature of class `Oro\Bundle\TranslationBundle\Provider\LanguageProvider` was changed:
    - use `Doctrine\Common\Persistence\ManagerRegistry` as first argument instead of `Doctrine\Common\Persistence\ObjectRepository`
    - use `@doctrine` as first service argument instead of `@oro_translation.repository.language`

FormBundle
----------
- Form types OroEncodedPlaceholderPasswordType, OroEncodedPasswordType acquired `browser_autocomplete` option with default value set to `false`, which means that password autocomplete is off by default.
