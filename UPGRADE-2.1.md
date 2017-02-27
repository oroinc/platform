UPGRADE FROM 2.0 to 2.1
========================

ActionBundle
------------
- `Oro\Bundle\ActionBundle\Condition\RouteExists` deprecated because of:
    - work with `RouteCollection` is performance consuming
    - it was used to check bundle presence, which could be done with `service_exists`
- Added aware interface `Oro\Bundle\ActionBundle\Provider\ApplicationProviderAwareInterface` and trait `ApplicationProviderAwareTrait`
- Added new action with alias `resolve_destination_page` and class `Oro\Bundle\ActionBundle\Action\ResolveDestinationPage`

ActivityListBundle
------------------
- Class `Oro\Bundle\ActivityListBundle\Filter`
    - construction signature was changed now it takes next arguments:
        - `FormFactoryInterface` $factory,
        - `FilterUtility` $util,
        - `ActivityAssociationHelper` $activityAssociationHelper,
        - `ActivityListChainProvider` $activityListChainProvider,
        - `ActivityListFilterHelper` $activityListFilterHelper,
        - `EntityRoutingHelper` $entityRoutingHelper,
        - `ServiceLink` $queryDesignerManagerLink,
        - `ServiceLink` $datagridHelperLink

AddressBundle
-------------
- Class `Oro\Bundle\AddressBundle\Twig\PhoneExtension`
    - construction signature was changed now it takes next arguments:
        - `ServiceLink` $providerLink

BatchBundle
-----------
- Added `Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator` that allows to iterate through changing dataset
- `Oro\Bundle\BatchBundle\ORM\Query\DeletionQueryResultIterator` is deprecated. Use `Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator` instead

DashboardBundle
---------------
- Class `Oro\Bundle\DashboardBundle\Twig\DashboardExtension`
    - construction signature was changed now it takes next arguments:
        - `ServiceLink` $converterLink,
        - `ServiceLink` $managerLink,
        - `EntityProvider` $entityProvider

DataAuditBundle
---------------
- Class `Oro\Bundle\DataAuditBundle\Filter\AuditFilter`
    - construction signature was changed now it takes next arguments:
        - `FormFactoryInterface` $factory,
        - `FilterUtility` $util,
        - `ServiceLink` $queryDesignerManagerLink

DataGridBundle
--------------
- `Oro\Bundle\DataGridBundle\Datasource\Orm\DeletionIterableResult` is deprecated. Use `Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator` instead

EmailBundle
-----------
- Added `Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface` and implemented it in `Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer`
- Class `Oro\Bundle\EmailBundle\Twig\EmailExtension`
    - construction signature was changed now it takes next arguments:
        - `EmailHolderHelper` $emailHolderHelper,
        - `EmailAddressHelper` $emailAddressHelper,
        - `EmailAttachmentManager` $emailAttachmentManager,
        - `EntityManager` $em,
        - `MailboxProcessStorage` $mailboxProcessStorage,
        - `SecurityFacade` $securityFacade,
        - `ServiceLink` $relatedEmailsProviderLink
- `Oro/Bundle/EmailBundle/Migrations/Data/ORM/EnableEmailFeature` removed, feature enabled by default
- Class `Oro\Bundle\EmailBundle\Async\Manager\AssociationManager`
    - changed the return type of `getOwnerIterator` method from `BufferedQueryResultIterator` to `\Iterator`

EntityBundle
------------
- Class `Oro\Bundle\EntityBundle\Twig\EntityFallbackExtension`
    - construction signature was changed now it takes next arguments:
        - `ServiceLink` $fallbackResolverLink
- Class `Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider`
    - added third argument for constructor `Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper $configHelper`
    - added sixth argument for method `public funtion getFields()` `$withRoutes = false`

EntityConfigBundle
------------------
- Class `Oro\Bundle\EntityConfigBundle\Config\ConfigManager`
    - removed property `protected $providers`
    - removed property `protected $propertyConfigs`
    - removed method `public function addProvider(ConfigProvider $provider)` in favor of `public function setProviderBag(ConfigProviderBag $providerBag)`
- Class `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider`
    - removed property `protected $propertyConfig`
    - construction signature was changed. The parameter `array $config` was replaced with `PropertyConfigBag $configBag`
- Class `Oro\Bundle\EntityConfigBundle\Config\ConfigCache`
    - removed property `protected $isDebug`
    - construction signature was changed. The optional parameter `$isDebug` was removed
    - changed the visibility of `cache` property from `protected` to `private`
    - changed the visibility of `modelCache` property from `protected` to `private`
    - the implementation was changed significantly, by performance reasons. The most of `protected` methods were removed or marked as `private`

EntityExtendBundle
------------------
- Class `Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension`
    - calls to `addManyToManyRelation`, `addManyToOneRelation` methods now create unidirectional relations.
    To create bidirectional relation you _MUST_ call `*InverseRelation` method respectively
    - call to `addOneToManyRelation` creates bidirectional relation according to Doctrine [documentation](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional)
    - deprecated `addOneToManyInverseRelation`
- Added parameter `FeatureChecker $featureChecker` to the constructor of `Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension`
- Added parameter `FeatureChecker $featureChecker` to the constructor of `Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension`
- Added parameter `FeatureChecker $featureChecker` to the constructor of `Oro\Bundle\EntityExtendBundle\Form\Extension`

EntityPaginationBundle
----------------------
- Class `Oro\Bundle\EntityPaginationBundle\Storage\StorageDataCollector`
    - construction signature was changed now it takes next arguments:
        - `ServiceLink` $dataGridManagerLink,
        - `DoctrineHelper` $doctrineHelper,
        - `AclHelper` $aclHelper,
        - `EntityPaginationStorage` $storage,
        - `EntityPaginationManager` $paginationManager

DataGridBundle
--------------
- Class `Oro\Bundle\DataGridBundle\Engine\Orm\PdoMysql\GroupConcat` was removed. Use `GroupConcat` from package `oro/doctrine-extensions` instead.
- Class `Oro\Bundle\DataGridBundle\Twig\DataGridExtension`
    - construction signature was changed now it takes next arguments:
        - `ServiceLink` $managerLink,
        - `NameStrategyInterface` $nameStrategy,
        - `RouterInterface` $router,
        - `SecurityFacade` $securityFacade,
        - `DatagridRouteHelper` $datagridRouteHelper,
        - `RequestStack` $requestStack,
        - `LoggerInterface` $logger = null

EntityConfigBundle
------------------
- Added parameter `ConfigDatabaseChecker $databaseChecker` to the constructor of `Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager`

ImapBundle
----------
- Updated `Oro\Bundle\ImapBundle\Async\SyncEmailMessageProcessor::__construct()` signature to use `Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface`.

ImportExportBundle
------------------
- Class `Oro\Bundle\ImportExportBundle\Async\Import\CliImportMessageProcessor`
    - construction signature was changed now it takes next arguments: 
        - `CliImportHandler $cliImportHandler`,
        - `JobRunner $jobRunner`,
        - `ImportExportResultSummarizer` $importExportResultSummarizer,
        - `JobStorage` $jobStorage,
        - `LoggerInterface` $logger,
        - `FileManager` $fileManager
- Class `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`
    - construction signature was changed now it takes next arguments: 
        - HttpImportHandler $httpImportHandler,
        - JobRunner $jobRunner,
        - MessageProducerInterface $producer,
        - RegistryInterface $doctrine,
        - TokenStorageInterface $tokenStorage,
        - ImportExportResultSummarizer $importExportResultSummarizer,
        - JobStorage $jobStorage,
        - LoggerInterface $logger,
        - FileManager $fileManager
- Class `Oro\Bundle\ImportExportBundle\Handler\AbstractHandler`
    - construction signature was changed now it takes next arguments: 
        - JobExecutor $jobExecutor,
        - ProcessorRegistry $processorRegistry,
        - ConfigProvider $entityConfigProvider,
        - TranslatorInterface $translator
- Class `Oro\Bundle\ImportExportBundle\Async\Import\PreCliImportMessageProcessor` and its service `oro_importexport.async.pre_cli_import` were added.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and its service `oro_importexport.async.pre_http_import` were added.
- Class `Oro\Bundle\ImportExportBundle\Splitter\SplitterChain` and its service `oro_importexport.async.send_import_error_notification` were added.
- Class `Oro\Bundle\ImportExportBundle\File\FileManager` and its service `oro_importexport.file.file_manager` were added. We should use it instead of the `Oro\Bundle\ImportExportBundle\File\FileSystemOperator`
- Class `Oro\Bundle\ImportExportBundle\File\FileSystemOperator` is deprecated now. Use `Oro\Bundle\ImportExportBundle\File\FileManager` instead.
- Command `oro:import:csv` (class `Oro\Bundle\ImportExportBundle\Command\ImportCommand`) was renamed to `oro:import:file`        
- Class `Oro\Bundle\ImportExportBundle\Async\Import\AbstractPreparingHttpImportMessageProcessor` and its service `oro_importexport.async.abstract_preparing_http_import` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\PreparingHttpImportMessageProcessor` and its service `oro_importexport.async.preparing_http_import` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\PreparingHttpImportValidationMessageProcessor` and its service `oro_importexport.async.preparing_http_import_validation` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\AbstractChunkImportMessageProcessor` and its service `oro_importexport.async.abstract_chunk_http_import` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\ChunkHttpImportMessageProcessor` and its service `oro_importexport.async.chunck_http_import` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\ChunkHttpImportValidationMessageProcessor` and its service `oro_importexport.async.chunck_http_import_validation` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\CliImportValidationMessageProcessor` and its service `oro_importexport.async.cli_import_validation` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreCliImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\CliImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\ImportExportJobSummaryResultService` was renamed to `ImportExportResultSummarizer`. It will be moved after add supporting templates in notification process.

LayoutBundle
------------
- Class `Oro\Bundle\LayoutBundle\DependencyInjection\CompilerOverrideServiceCompilerPass` was removed

LocaleBundle
------------
- Class `Oro\Bundle\LocaleBundle\Formatter\AddressFormatter`
    - construction signature was changed now it takes next arguments:
        - `LocaleSettings` $localeSettings,
        - `NameFormatter` $nameFormatter,
        - `PropertyAccessor` $propertyAccessor
- Class `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue`
    - will become not extended in 2.3 release
- Class `Oro\Bundle\LocaleBundle\Model\ExtendLocalizedFallbackValue`
    - deprecated and will be removed in 2.3 release

NavigationBundle
----------------
- `Oro\Bundle\NavigationBundle\Manager`:
    - added method `moveMenuItems`

SearchBundle
------------
- `DbalStorer` is deprecated. If you need its functionality, please compose your class with `DBALPersistenceDriverTrait`
- Deprecated services and classes:
    - `oro_search.search.engine.storer`
    - `Oro\Bundle\SearchBundle\Engine\Orm\DbalStorer`
- `entityManager` instead of `em` should be used in `BaseDriver` children
- `OrmIndexer` should be decoupled from `DbalStorer` dependency
- Interface `Oro\Bundle\SearchBundle\Engine\EngineV2Interface` marked as deprecated - please, use
`Oro\Bundle\SearchBundle\Engine\EngineInterface` instead
- Return value types in `Oro\Bundle\SearchBundle\Query\SearchQueryInterface` and
`Oro\Bundle\SearchBundle\Query\AbstractSearchQuery` were fixed to support fluent interface
`Oro\Bundle\SearchBundle\Engine\Orm` `setDrivers` method and `$drivers` and injected directly to `Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository`
`Oro\Bundle\SearchBundle\Engine\OrmIndexer` `setDrivers` method and `$drivers` and injected directly to `Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository`

ScopeBundle
-----------
- Class `Oro\Bundle\ScopeBundle\Manager\ScopeManager`
    - changed the return type of `findBy` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `findRelatedScopes` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`


SecurityBundle
--------------
- Service overriding in compiler pass was replaced by service decoration for next services:
    - `sensio_framework_extra.converter.doctrine.orm`
    - `security.acl.dbal.provider`
    - `security.acl.cache.doctrine`
    - `security.acl.voter.basic_permissions`
- Next container parameters were removed:
    - `oro_security.acl.voter.class`
- `Oro\Bundle\SecurityBundle\Owner\AbstractOwnerTreeProvider`:
    - removed implementation of `Symfony\Component\DependencyInjection\ContainerAwareInterface`
    - removed method `public function setContainer(ContainerInterface $container = null)`
    - removed method `protected function getContainer()`
    - changed the visibility of `$tree` property from `protected` to `private`
    - removed method `public function getCache()`
    - removed method `protected function getTreeData()`
    - removed method `protected function getOwnershipMetadataProvider()`
    - removed method `protected function checkDatabase()`
    - removed method `getManagerForClass($className)`
- `Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider`:
    - removed constant `CACHE_KEY`
    - removed property `protected $em`
    - removed method `public function getCache()`
    - changed the signature of the constructor.
      Old signature: `__construct(EntityManager $em, CacheProvider $cache)`.
      New signature:
        ```
        __construct(
            ManagerRegistry $doctrine,
            DatabaseChecker $databaseChecker,
            CacheProvider $cache,
            MetadataProviderInterface $ownershipMetadataProvider,
            TokenStorageInterface $tokenStorage
        )
        ```
- `Oro\Bundle\SecurityBundle\Form\Extension\AclProtectedFieldTypeExtension`:
    - removed parameter `EntityClassResolver $entityClassResolver` from the constructor
    - removed property `protected $entityClassResolver`

TranslationBundle
-----------------
- Added parameter `ConfigDatabaseChecker $databaseChecker` to the constructor of `Oro\Bundle\TranslationBundle\Translation\OrmTranslationLoader`

UIBundle
--------
- Class `Oro\Bundle\UIBundle\Twig\FormatExtension`
    - construction signature was changed now it takes next arguments:
        - `ServiceLink` $formatterManagerLink

UserBundle
----------
- Class `Oro\Bundle\UserBundle\Security\ImpersonationAuthenticator`
    - replaced parameter `EntityManager $em` with `ManagerRegistry $doctrine` in the constructor
    - removed property `protected $em`

WorkflowBundle
--------------
- Class `Oro\Bundle\WorkflowBundle\Validator\WorkflowValidationLoader`:
    - replaced parameter `ServiceLink $emLink` with `ConfigDatabaseChecker $databaseChecker` in the constructor
    - removed property `protected $emLink`
    - removed property `protected $dbCheck`
    - removed property `protected $requiredTables`
    - removed method `protected function checkDatabase()`
    - removed method `protected function getEntityManager()`
- Created action `@get_available_workflow_by_record_group`
    - class `Oro\Bundle\WorkflowBundle\Model\Action\GetAvailableWorkflowByRecordGroup`
- Class `Oro\Bundle\WorkflowBundle\Acl\Extension\AbstractWorkflowAclExtension`
    - signature of constructor changed, fifth argument `WorkflowRegistry $workflowRegistry` replaced by `WorkflowManager $workflowManager`
- Class `Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclExtension`
    - signature of constructor changed, fifth argument `WorkflowRegistry $workflowRegistry` replaced by `WorkflowManager $workflowManager`
- Class `Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowTransitionAclExtension`
    - signature of constructor changed, fifth argument `WorkflowRegistry $workflowRegistry` replaced by `WorkflowManager $workflowManager`
- Class `Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemListener`
    - signature of constructor changed, second argument `WorkflowRegistry $workflowRegistry` replaced by `WorkflowManagerRegistry $workflowManagerRegistry`
- Class `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry`
    - signature of constructor changed, added third argument `Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionFilters $definitionFilters`
- Added third argument `string $responseMessage = null` to method `Oro\Bundle\WorkflowBundle\Handle\Helper\TransitionHelper::createCompleteResponse()`
- Added third argument `Oro\Bundle\ActionBundle\Resolver\DestinationPageResolver $destinationPageResolver` to constructor of `Oro\Bundle\WorkflowBundle\Extension\AbstractButtonProviderExtension`
- Class `Oro\Bundle\WorkflowBundle\Provider\WorkflowDataProvider`
    - first argument argument `WorkflowManager $workflowManager` replaced by `WorkflowManagerRegistry $workflowManagerRegistry`

TestFrameworkBundle
-------------------
- `@dbIsolation annotation removed, applied as defult behavior`
- `@dbReindex annotation removed, use \Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait::clearIndexTextTable`
- `Oro/Bundle/TestFrameworkBundle/Test/Client`:
    - removed property `$pdoConnection`
    - removed property `$kernel`
    - removed property `$hasPerformedRequest`
    - removed property `$loadedFixtures`
    - removed method `reboot`
    - removed method `doRequest`
- `Oro/Bundle/TestFrameworkBundle/Test/WebTestCase`:
    - removed property `$dbIsolation`
    - removed property `$dbReindex`
    - removed method `getDbIsolationSetting`
    - removed method `getDbReindexSetting`
    - removed method `getDbReindexSetting`
    - renamed method `setUpBeforeClass` to `beforeClass`
    - renamed method `tearDownAfterClass` to `afterClass`

Tree Component
- `Oro\Component\Tree\Handler\AbstractTreeHandler`:
    - added method `getTreeItemList`

QueryDesignerBundle
-------------------
- Class `Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationQueryConverter`
    - construction signature was changed now it takes next arguments:
        `FunctionProviderInterface` $functionProvider,
        `VirtualFieldProviderInterface` $virtualFieldProvider,
        `ManagerRegistry` $doctrine,
        `DatagridGuesser` $datagridGuesser,
        `EntityNameResolver` $entityNameResolver
- Class `Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder`
    - construction signature was changed now it takes next arguments:
        `FunctionProviderInterface` $functionProvider,
        `VirtualFieldProviderInterface` $virtualFieldProvider,
        `ManagerRegistry` $doctrine,
        `DatagridGuesser` $datagridGuesser,
        `EntityNameResolver` $entityNameResolver

TagBundle
---------
- Class `Oro\Bundle\TagBundle\Grid\AbstractTagsExtension`
    - removed method `isReportOrSegmentGrid`
    - removed method `addReportOrSegmentGridPrefix`
    - added UnsupportedGridPrefixesTrait
