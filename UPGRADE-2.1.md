UPGRADE FROM 2.0 to 2.1
========================

#### General
- Changed minimum required php version to 7.0
- Updated dependency to [fxpio/composer-asset-plugin](https://github.com/fxpio/composer-asset-plugin) composer plugin to version 1.3.
- Composer updated to version 1.4.

```
    composer self-update
    composer global require "fxp/composer-asset-plugin"
```

MessageQueue Component
----------------------
- Class `Oro\Component\MessageQueue\Client\Meta\DestinationsCommand`
    - removed the constructor and implement `ContainerAwareInterface`
- Class `Oro\Component\MessageQueue\Client\Meta\TopicsCommand`
    - removed the constructor and implement `ContainerAwareInterface`
- Class `Oro\Component\MessageQueue\Client\ConsumeMessagesCommand`
    - removed the constructor and implement `ContainerAwareInterface`
    - removed property `protected $consumer`
    - removed property `protected $processor`
- Class `Oro\Component\MessageQueue\Client\CreateQueuesCommand`
    - removed the constructor and implement `ContainerAwareInterface`
- Class `Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand`
    - removed the constructor
    - removed property `protected $consumer`
- Unify percentage value for `Job::$jobProgress`. Now 100% is stored as 1 instead of 100.
- Unused class `Oro\Component\MessageQueue\Job\CalculateRootJobProgressService` was removed
- Class `Oro\Component\MessageQueue\Job\CalculateRootJobStatusService` was renamed to `Oro\Component\MessageQueue\Job\RootJobStatusCalculator`

ChainProcessor Component
------------------------
- Fixed an issue with invalid execution order of processors. The issue was that processors from different groups are intersected. During the fix the calculation of internal priorities of processors was changed, this may affect existing configuration of processors in case if you have common (not bound to any action) processors and ungrouped processors which should work with regular grouped processors.

    The previous priority rules:

    | Processor type | Processor priority | Group priority |
    |----------------|--------------------|----------------|
    | initial common processors | from -255 to 255 |  |
    | initial ungrouped processors | from -255 to 255 |  |
    | grouped processors | from -255 to 255 | from -254 to 252 |
    | final ungrouped processors | from -65535 to -65280 |  |
    | final common processors | from min int to -65536 |  |

    The new priority rules:

    | Processor type | Processor priority | Group priority |
    |----------------|--------------------|----------------|
    | initial common processors | greater than or equals to 0 |  |
    | initial ungrouped processors | greater than or equals to 0 |  |
    | grouped processors | from -255 to 255 | from -255 to 255 |
    | final ungrouped processors | less than 0 |  |
    | final common processors | less than 0 |  |

    So, the new rules means that

        - common and ungrouped processors with the priority greater than or equals to 0 will be executed before grouped processors
        - common and ungrouped processors with the priority less than 0 will be executed after grouped processors
        - now there are no any magic numbers for priorities of any processors

Action Component
----------------
- Added interface `Oro\Component\Action\Model\DoctrineTypeMappingExtensionInterface`.
- Added Class `Oro\Component\Action\Model\DoctrineTypeMappingExtension`. That can be used as base for services definitions

ActionBundle
------------
- `Oro\Bundle\ActionBundle\Condition\RouteExists` deprecated because of:
    - work with `RouteCollection` is performance consuming
    - it was used to check bundle presence, which could be done with `service_exists`
- Added aware interface `Oro\Bundle\ActionBundle\Provider\ApplicationProviderAwareInterface` and trait `ApplicationProviderAwareTrait`
- Added new action with alias `resolve_destination_page` and class `Oro\Bundle\ActionBundle\Action\ResolveDestinationPage`
- The service `oro_action.twig.extension.operation` was marked as `private`
- Class `Oro\Bundle\ActionBundle\Twig\OperationExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $routeProvider`
    - removed property `protected $contextHelper`
    - removed property `protected $optionsHelper`
    - removed property `protected $buttonProvider`
    - removed property `protected $searchContextProvider`
- Added interfaces `Oro\Bundle\ActionBundle\Model\ParameterInterface` and `Oro\Bundle\ActionBundle\Model\EntityParameterInterface`
- Implemented `Oro\Bundle\ActionBundle\Model\EntityParameterInterface` interface in `Oro\Bundle\ActionBundle\Model\Attribute` class
- Added `getInternalType()` method to `Oro\Bundle\ActionBundle\Model\Attribute` class
- Added new tag `oro.action.extension.doctrine_type_mapping` to collect custom doctrine type mappings used to resolve types for serialization at `Oro\Bundle\ActionBundle\Model\AttributeGuesser` 
- Added second optional argument `Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria $criteria = null` to method `Oro\Bundle\ActionBundle\Model\OperationRegistry`

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
- The parameter `oro_address.twig.extension.phone.class` was removed from DIC
- The service `oro_address.twig.extension.phone` was marked as `private`
- The service `oro_address.provider.phone.link` was removed
- Class `Oro\Bundle\AddressBundle\Twig\PhoneExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $providerLink`

AsseticBundle
-------------
- The parameter `oro_assetic.twig_extension.class` was removed from DIC
- The service `oro_assetic.twig.extension` was marked as `private`

AttachmentBundle
----------------
- The parameter `oro_attachment.twig.file_extension.class` was removed from DIC
- The service `oro_attachment.twig.file_extension` was marked as `private`
- Class `Oro\Bundle\AttachmentBundle\Twig\FileExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $manager`
    - removed property `protected $attachmentConfigProvider`
    - removed property `protected $doctrine`
- Class `Oro\Bundle\AttachmentBundle\Manager\FileManager`
    - method `writeStreamToStorage` was changed to `public`

BatchBundle
-----------
- Added `Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator` that allows to iterate through changing dataset
- `Oro\Bundle\BatchBundle\ORM\Query\DeletionQueryResultIterator` is deprecated. Use `Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator` instead

ConfigBundle
------------
- The parameter `oro_config.twig_extension.class` was removed from DIC
- The service `oro_config.twig.config_extension` was marked as `private`
- Class `Oro\Bundle\ConfigBundle\Twig\ConfigExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $cm`

CurrencyBundle
--------------
- The parameter `oro_currency.twig.currency.class` was removed from DIC
- The service `oro_currency.twig.currency` was marked as `private`
- Class `Oro\Bundle\CurrencyBundle\Twig\CurrencyExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`
    - removed property `protected $provider`
    - removed property `protected $currencyNameHelper`

DashboardBundle
---------------
- Class `Oro\Bundle\DashboardBundle\Twig\DashboardExtension`
    - construction signature was changed now it takes next arguments:
        - `ServiceLink` $converterLink,
        - `ServiceLink` $managerLink,
        - `EntityProvider` $entityProvider
- The service `oro_dashboard.widget_config_value.date_range.converter.link` was removed
- The service `oro_dashboard.twig.extension` was marked as `private`
- Class `Oro\Bundle\DashboardBundle\Twig\DashboardExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $converter`
    - removed property `protected $managerLink`
    - removed property `protected $entityProvider`
- Class `Oro\Bundle\DashboardBundle\Provider\BigNumber\BigNumberProcessor`
    - construction signature was changed. The parameter `OwnerHelper $ownerHelper` was removed
    - removed property `protected $ownerHelper`


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
- Class `Oro\Bundle\DataGridBundle\Engine\Orm\PdoMysql\GroupConcat` was removed. Use `GroupConcat` from package `oro/doctrine-extensions` instead.
- The service `oro_datagrid.twig.datagrid` was marked as `private`
- Class `Oro\Bundle\DataGridBundle\Twig\DataGridExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $managerLink`
    - removed property `protected $nameStrategy`
    - removed property `protected $router`
    - removed property `protected $securityFacade`
    - removed property `protected $datagridRouteHelper`
    - removed property `protected $requestStack`
    - removed property `protected $logger`
- Added method `public function getName()::string` to interface `Oro\Bundle\DataGridBundle\Extension\GridViews\ViewInterface`
- Class `Oro\Bundle\DataGridBundle\Controller\GridController`
   - renamed method `filterMetadata` to `filterMetadataAction`
- Class `Oro\Bundle\DataGridBundle\Async\Export\ExportMessageProcessor`
    - construction signature was changed now it takes next arguments:
        - ExportHandler $exportHandler,
        - JobRunner $jobRunner,
        - DatagridExportConnector $exportConnector,
        - ExportProcessor $exportProcessor,
        - WriterChain $writerChain,
        - TokenStorageInterface $tokenStorage,
        - JobStorage $jobStorage,
        - LoggerInterface $logger
- Class `Oro\Bundle\DataGridBundle\Async\Export\PreExportMessageProcessor` and its service `oro_datagrid.async.pre_export` were added.
- Class `Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher` and its service `oro_datagrid.importexport.export_id_fetcher` were added.
- Class `Oro\Bundle\DataGridBundle\Handler\ExportHandler` (service `oro_datagrid.handler`) changed its service calls: it doesn't call `setRouter` and `setConfigManager` any more but calls `setFileManager` now.
- Topic `oro.datagrid.export` doesn't start datagrid export any more. Use `oro.datagrid.pre_export` topic instead.

DistributionBundle
------------------
- The method `Oro\Bundle\DistributionBundle\Error\ErrorHandler::handle` is deprecated. Use `Oro\Bundle\DistributionBundle\Error\ErrorHandler::handleErrors` instead.

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
- Class `Oro\Bundle\EmailBundle\EventListener\AutoResponseListener`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
- The parameter `oro_email.twig.extension.email.class` was removed from DIC
- The service `oro_email.twig.extension.email` was marked as `private`
- The service `oro_email.link.autoresponserule_manager` was marked as deprecated
- Class `Oro\Bundle\EmailBundle\Twig\EmailExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $emailHolderHelper`
    - removed property `protected $emailAddressHelper`
    - removed property `protected $emailAttachmentManager`
    - removed property `protected $em`
    - removed property `protected $relatedEmailsProviderLink`

EmbeddedFormBundle
------------------
- The parameter `oro_embedded_form.back_link.twig.extension.class` was removed from DIC
- The service `oro_embedded_form.back_link.twig.extension` was marked as `private`
- Class `Oro\Bundle\EmbeddedFormBundle\Twig\BackLinkExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $router`
    - removed property `protected $translator`

EntityBundle
------------
- Class `Oro\Bundle\EntityBundle\Twig\EntityFallbackExtension` was removed
- Class `Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider`
    - added third argument for constructor `Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper $configHelper`
    - added sixth argument for method `getFields` `$withRoutes = false`
- The parameter `oro_entity.twig.extension.entity.class` was removed from DIC
- The service `oro_entity.twig.extension.entity` was marked as `private`
- The service `oro_entity.fallback.resolver.entity_fallback_resolver.link` was removed
- Class `Oro\Bundle\EntityBundle\Twig\EntityFallbackExtension` was removed
- Class `Oro\Bundle\EntityBundle\Twig\EntityExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $entityIdAccessor`
    - removed property `protected $entityRoutingHelper`
    - removed property `protected $entityNameResolver`
    - removed property `protected $entityAliasResolver`
- Added class `Oro\Bundle\EntityBundle\ORM\DiscriminatorMapListener' that should be used for entities with single table inheritance.
  Example:
```yml
oro_acme.my_entity.discriminator_map_listener:
    class: 'Oro\Bundle\EntityBundle\ORM\DiscriminatorMapListener'
    public: false
    calls:
        - [ addClass, ['oro_acme_entity', '%oro_acme.entity.acme_entity.class%'] ]
    tags:
        - { name: doctrine.event_listener, event: loadClassMetadata }
```

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
- Added parameter `ConfigDatabaseChecker $databaseChecker` to the constructor of `Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager`
- The parameter `oro_entity_config.twig.extension.config.class` was removed from DIC
- The service `oro_entity_config.twig.extension.config` was marked as `private`
- The service `oro_entity_config.twig.extension.dynamic_fields_attribute_decorator` was marked as `private`
- The service `oro_entity_config.link.config_manager` was marked as deprecated
- Class `Oro\Bundle\EntityConfigBundle\Twig\ConfigExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $configManager`
    - removed property `protected $entityClassNameHelper`
    - removed property `protected $doctrineHelper`
- Class `Oro\Bundle\EntityConfigBundle\Twig\DynamicFieldsExtensionAttributeDecorator`
    - the construction signature of was changed. Now the constructor has the following parameters `AbstractDynamicFieldsExtension $extension, ContainerInterface $container`

EntityExtendBundle
------------------
- Class `Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension`
    - calls to `addManyToManyRelation`, `addManyToOneRelation` methods now create unidirectional relations.
    To create bidirectional relation you _MUST_ call `*InverseRelation` method respectively
    - call to `addOneToManyRelation` creates bidirectional relation according to Doctrine [documentation](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional)
    - deprecated `addOneToManyInverseRelation`
    - throw exception when trying to use not allowed option while creating relation in migration
- To be able to create bidirectional relation between entities and use "Reuse existing relation" functionality on UI
    you _MUST_ select "bidirectional" field while creating relation
- The parameter `oro_entity_extend.twig.extension.dynamic_fields.class` was removed from DIC
- The parameter `oro_entity_extend.twig.extension.enum.class` was removed from DIC
- The service `oro_entity_extend.twig.extension.dynamic_fields` was marked as `private`
- The service `oro_entity_extend.twig.extension.enum` was marked as `private`
- Class `Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - the visibility of method `filterFields` was changed from `public` to `private`
    - the visibility of method `createDynamicFieldRow` was changed from `protected` to `private`
    - removed method `createDynamicFieldRows`
    - removed property `protected $fieldTypeHelper`
    - removed property `protected $extendProvider`
    - removed property `protected $entityProvider`
    - removed property `protected $viewProvider`
    - removed property `protected $propertyAccessor`
    - removed property `protected $eventDispatcher`
    - removed property `protected $securityFacade`
- Added parameter `FeatureChecker $featureChecker` to the constructor of `Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension`
- Added parameter `FeatureChecker $featureChecker` to the constructor of `Oro\Bundle\EntityExtendBundle\Form\Extension`
- Class `Oro\Bundle\EntityExtendBundle\Grid\AbstractFieldsExtension`
    - the construction signature of was changed. Added fourth argument `FieldsHelper $fieldsHelper`
- Added parameter `FieldsHelper $fieldsHelper` to the constructor of `Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension`
- Added parameter `FieldsHelper $fieldsHelper` to the constructor of `Oro\Bundle\EntityExtendBundle\Grid\AdditionalFieldsExtension`

EntityMergeBundle
-----------------
- The parameter `oro_entity_merge.twig.extension.class` was removed from DIC
- The service `oro_entity_merge.twig.extension` was marked as `private`
- Class `Oro\Bundle\EntityMergeBundle\Twig\MergeExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $accessor`
    - removed property `protected $fieldValueRenderer`
    - removed property `protected $translator`

EntityPaginationBundle
----------------------
- Class `Oro\Bundle\EntityPaginationBundle\Storage\StorageDataCollector`
    - construction signature was changed now it takes next arguments:
        - `ServiceLink` $dataGridManagerLink,
        - `DoctrineHelper` $doctrineHelper,
        - `AclHelper` $aclHelper,
        - `EntityPaginationStorage` $storage,
        - `EntityPaginationManager` $paginationManager
- The parameter `oro_entity_pagination.twig_extension.entity_pagination.class` was removed from DIC
- The service `oro_entity_pagination.twig_extension.entity_pagination` was marked as `private`
- Class `Oro\Bundle\EntityPaginationBundle\Twig\EntityPaginationExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed method `setRequest`
    - removed property `protected $paginationNavigation`
    - removed property `protected $dataCollector`
    - removed property `protected $messageManager`
    - removed property `protected $request`

FeatureToggleBundle
-------------------
- The service `oro_featuretoggle.twig.feature_extension` was marked as `private`
- Class `Oro\Bundle\FeatureToggleBundle\Twig\FeatureExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $featureChecker`


FormBundle
----------
- The parameter `oro_form.twig.form.class` was removed from DIC
- The parameter `oro_form.twig.js_validation_extension.class` was removed from DIC
- The service `oro_form.twig.form_extension` was marked as `private`
- The service `oro_form.twig.js_validation_extension` was removed
- Class `Oro\Bundle\FormBundle\Twig\JsValidationExtension` was removed. Its functionality was moved to `Oro\Bundle\FormBundle\Twig\FormExtension`

- Class `Oro\Bundle\FormBundle\Model\UpdateHandlerFacade` added as a replacement of standard `Oro\Bundle\FormBundle\Model\UpdateHandler`.
So please consider to use it when for a new entity management development.

- Class `Oro\Bundle\FormBundle\Model\UpdateHandler`
    - marked as deprecated, use `Oro\Bundle\FormBundle\Model\UpdateHandlerFacade` (service `oro_form.update_handler`) instead
    - changed `__constructor` signature: 
        - first argument changed from `Symfony\Component\HttpFoundation\Request` to `Symfony\Component\HttpFoundation\RequestStack`
        - fifth argument changed from `Symfony\Component\EventDispatcher\EventDispatcherInterface` to `Oro\Bundle\FormBundle\Form\Handler\FormHandler` as from handling encapsulation.
 
- Interface `Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface` added for standard form handlers.
- Class `Oro\Bundle\FormBundle\Form\Handler\FormHandler` added (service 'oro_form.form.handler.default') as default form processing mechanism.
- Tag `oro_form.form.handler` added to register custom form handlers under its `alias`.
- Class `Oro\Bundle\FormBundle\Model\FormHandlerRegistry` added to collect tagged with `oro_form.form.handler` services.
- Class `Oro\Bundle\FormBundle\Form\Handler\CallbackFormHandler` added as interface compatibility helper for callable.

- Interface `Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface`  added for common update template data population.
- Class `Oro\Bundle\FormBundle\Provider\FromTemplateDataProvider` (service `oro_form.provider.from_template_data.default`) as default update template data provider.
- Tag `oro_form.form_template_data_provider` added to register custom update template data providers.
- Class `Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry` added to collect tagged with `oro_form.form_template_data_provider` services.
- Class `Oro\Bundle\FormBundle\Provider\CallbackFormTemplateDataProvider` added as interface compatibility helper for callable.

HelpBundle
----------
- The parameter `oro_help.twig.extension.class` was removed from DIC
- The service `oro_help.twig.extension` was marked as `private`
- Class `Oro\Bundle\HelpBundle\Twig\HelpExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $linkProvider`

ImapBundle
----------
- Updated `Oro\Bundle\ImapBundle\Async\SyncEmailMessageProcessor::__construct()` signature to use `Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface`.

ImportExportBundle
------------------
- Class `Oro\Bundle\ImportExportBundle\Async\Import\CliImportMessageProcessor`
    - construction signature was changed now it takes next arguments: 
        - CliImportHandler $cliImportHandler,
        - JobRunner $jobRunner,
        - ImportExportResultSummarizer $importExportResultSummarizer,
        - JobStorage $jobStorage,
        - LoggerInterface $logger,
        - FileManager $fileManager
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
        - WriterChain $writerChain,
        - ReaderChain $readerChain,
        - BatchFileManager $batchFileManager
- Class `Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor`
    - changed the namespace from `Oro\Bundle\ImportExportBundle\Async` to `Oro\Bundle\ImportExportBundle\Async\Export`
    - construction signature was changed now it takes next arguments:
        - ExportHandler $exportHandler,
        - JobRunner $jobRunner,
        - DoctrineHelper $doctrineHelper,
        - TokenStorageInterface $tokenStorage,
        - LoggerInterface $logger,
        - JobStorage $jobStorage
- Class `Oro\Bundle\ImportExportBundle\Handler\AbstractImportHandler` (service `oro_importexport.handler.import.abstract`) changed its service calls: it doesn't call `setRouter` and `setConfigManager` any more but calls `setReaderChain` now.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\PreCliImportMessageProcessor` and its service `oro_importexport.async.pre_cli_import` were added.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and its service `oro_importexport.async.pre_http_import` were added.
- Class `Oro\Bundle\ImportExportBundle\Splitter\SplitterChain` and its service `oro_importexport.async.send_import_error_notification` were added.
- Class `Oro\Bundle\ImportExportBundle\File\FileManager` and its service `oro_importexport.file.file_manager` were added. We should use it instead of the `Oro\Bundle\ImportExportBundle\File\FileSystemOperator`
- Class `Oro\Bundle\ImportExportBundle\File\FileSystemOperator` is deprecated now. Use `Oro\Bundle\ImportExportBundle\File\FileManager` instead.
- Command `oro:cron:import-clean-up-storage` (class `Oro\Bundle\ImportExportBundle\Command\Cron\CleanupStorageCommand`) was added.
- Command `oro:import:csv` (class `Oro\Bundle\ImportExportBundle\Command\ImportCommand`) was renamed to `oro:import:file`
- Class `Oro\Bundle\ImportExportBundle\Async\Import\AbstractPreparingHttpImportMessageProcessor` and its service `oro_importexport.async.abstract_preparing_http_import` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\PreparingHttpImportMessageProcessor` and its service `oro_importexport.async.preparing_http_import` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\PreparingHttpImportValidationMessageProcessor` and its service `oro_importexport.async.preparing_http_import_validation` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\AbstractChunkImportMessageProcessor` and its service `oro_importexport.async.abstract_chunk_http_import` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\ChunkHttpImportMessageProcessor` and its service `oro_importexport.async.chunck_http_import` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\ChunkHttpImportValidationMessageProcessor` and its service `oro_importexport.async.chunck_http_import_validation` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Async\Import\CliImportValidationMessageProcessor` and its service `oro_importexport.async.cli_import_validation` were removed. You can use `Oro\Bundle\ImportExportBundle\Async\Import\PreCliImportMessageProcessor` and `Oro\Bundle\ImportExportBundle\Async\Import\CliImportMessageProcessor`.
- Class `Oro\Bundle\ImportExportBundle\Splitter\SplitterCsvFiler` and its service `oro_importexport.splitter.csv` were removed. You can use `Oro\Bundle\ImportExportBundle\File\BatchFileManager` instead.
- Class `Oro\Bundle\ImportExportBundle\Async\ImportExportJobSummaryResultService` was renamed to `ImportExportResultSummarizer`. It will be moved after add supporting templates in notification process.
- Route `oro_importexport_import_error_log` with path `/import_export/import-error/{jobId}.log` was renamed to `oro_importexport_job_error_log` with path `/import_export/job-error-log/{jobId}.log`

InstallerBundle
---------------
- The parameter `oro_installer.listener.request.class` was removed from DIC

IntegrationBundle
-----------------
- The parameter `oro_integration.twig.integration.class` was removed from DIC
- The service `oro_integration.twig.integration` was marked as `private`

LayoutBundle
------------
- Class `Oro\Bundle\LayoutBundle\DependencyInjection\CompilerOverrideServiceCompilerPass` was removed
- Removed the following parameters from the DI container:
    - `oro_layout.layout_factory_builder.class`
    - `oro_layout.twig.extension.layout.class`
    - `oro_layout.twig.renderer.class`
    - `oro_layout.twig.renderer.engine.class`
    - `oro_layout.twig.layout_renderer.class`
    - `oro_layout.twig.form.engine.class`
- Class `Oro\Bundle\LayoutBundle\Form\TwigRendererEngine`
    - removed property `$layoutHelper`
    - removed method `setLayoutHelper`
- Class `Oro\Bundle\LayoutBundle\EventListener\LayoutListener`
    - construction signature was changed, parameter `LayoutManager $layoutManager` was replaced with `ContainerInterface $container`
    - the visibility of `$layoutHelper` property changed from `protected` to `private`
    - removed property `protected $layoutManager`
    - signature of `getLayoutResponse` method was changed, added parameter `LayoutManager $layoutManager`
- Class `Oro\Bundle\LayoutBundle\Request\LayoutHelper`
    - construction signature was changed, parameter `ConfigManager $configManager` was removed
    - removed method `isProfilerEnabled`
    - removed property `$configManager`
    - removed property `$profilerEnabled`
- Class `Oro\Bundle\LayoutBundle\Twig\LayoutExtension`
    - construction signature was changed, now it takes only `ContainerInterface $container`
- Class `Oro\Bundle\LayoutBundle\EventListener\ImagineFilterConfigListener`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $imageFilterLoader`
- Class `Oro\Bundle\LayoutBundle\Form\TwigRendererEngine`
    - removed method `setLayoutHelper`
    - removed property `protected $layoutHelper`
- Class `Oro\Bundle\LayoutBundle\Request\LayoutHelper`
    - the construction signature of was changed. The parameter `ConfigManager $configManager` was removed
    - removed method `isProfilerEnabled`
    - removed property `protected $configManager`
    - removed property `protected $profilerEnabled`
- Changed default value option name for `page_title` block type, from `text` to `defaultValue`
- Added alias `layout` for `oro_layout.layout_manager` service to make it more convenient to access it from container

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
- Removed the following parameters from DIC:
    - `oro_locale.twig.date_format.class`
    - `oro_locale.twig.locale.class`
    - `oro_locale.twig.calendar.class`
    - `oro_locale.twig.date_time.class`
    - `oro_locale.twig.name.class`
    - `oro_locale.twig.address.class`
    - `oro_locale.twig.number.class`
- The following services were marked as `private`:
    - `oro_locale.twig.date_format`
    - `oro_locale.twig.locale`
    - `oro_locale.twig.calendar`
    - `oro_locale.twig.address`
    - `oro_locale.twig.number`
    - `oro_locale.twig.localization`
    - `oro_locale.twig.date_time_organization`
- The service `oro_locale.twig.name` was removed
- Class `Oro\Bundle\LocaleBundle\Formatter\CurrencyFormatter`
    - the construction signature of was changed. The parameter `NumberExtension $numberExtension` was replaced with `NumberFormatter $formatter`
    - removed property `protected $numberExtension`
- Class `Oro\Bundle\LocaleBundle\Twig\NameExtension` was removed
- Class `Oro\Bundle\LocaleBundle\Twig\AddressExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`
- Class `Oro\Bundle\LocaleBundle\Twig\CalendarExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $localeSettings`
- Class `Oro\Bundle\LocaleBundle\Twig\DateFormatExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $converterRegistry`
- Class `Oro\Bundle\LocaleBundle\Twig\DateTimeExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`
- Class `Oro\Bundle\LocaleBundle\Twig\DateTimeOrganizationExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`
    - removed property `protected $configManager`
- Class `Oro\Bundle\LocaleBundle\Twig\LocaleExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $localeSettings`
- Class `Oro\Bundle\LocaleBundle\Twig\LocalizationExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $languageCodeFormatter`
    - removed property `protected $formattingCodeFormatter`
    - removed property `protected $localizationHelper`
- Class `Oro\Bundle\LocaleBundle\Twig\NumberExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`
- Class `Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider`
    - changed `__constructor` signature:
        - the third argument changed from `Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter` to `Oro\Bundle\TranslationBundle\Provider\LanguageProvider`
- The service `oro_translation.event_listener.language_change` was removed
- The class `Oro\Bundle\TranslationBundle\EventListener\LanguageChangeListener` was removed

MessageQueueBundle
------------------
- The service `oro_message_queue.job.calculate_root_job_status_service` was renamed to `oro_message_queue.job.root_job_status_calculator` and marked as `private`
- The service `oro_message_queue.job.calculate_root_job_progress_service` was renamed to `oro_message_queue.job.root_job_progress_calculator` and marked as `private`

MigrationBundle
---------------
- The parameter `oro_migration.twig.schema_dumper.class` was removed from DIC
- The service `oro_migration.twig.schema_dumper` was marked as `private`
- Class `Oro\Bundle\MigrationBundle\Twig\HelpExtension`
    - property `$managerRegistry` was renamed to `$doctrine`

NavigationBundle
----------------
- `Oro\Bundle\NavigationBundle\Manager`:
    - added method `moveMenuItems`
- Class `Oro\Bundle\NavigationBundle\Datagrid\MenuUpdateDatasource`
    - the construction signature of was changed. Added parameter `MenuConfiguration $menuConfiguration`
    - removed method `setMenuConfiguration`
    - changed type of property `protected $menuConfiguration` from `array` to `MenuConfiguration`
- Class `Oro\Bundle\NavigationBundle\Event\RequestTitleListener`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
- Class `Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder`
    - the construction signature of was changed. Added parameter `MenuConfiguration $menuConfiguration`
    - removed method `setConfiguration`
    - removed property `protected $configuration`
- Removed the following parameters from DIC:
    - `oro_menu.twig.extension.class`
    - `oro_navigation.event.master_request_route_listener.class`
    - `oro_navigation.title_service.twig.extension.class`
    - `oro_navigation.title_service.event.request.listener.class`
    - `oro_navigation.twig_hash_nav_extension.class`
- The following services were marked as `private`:
    - `oro_menu.twig.extension`
    - `oro_navigation.title_service.twig.extension`
- Class `Oro\Bundle\NavigationBundle\Twig\MenuExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed method `setBreadcrumbManager`
    - removed property `protected $menuConfiguration`
    - removed property `protected $breadcrumbManager`
- Class `Oro\Bundle\NavigationBundle\Twig\TitleExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $titleService`

OrganizationBundle
------------------
- Removed the following parameters from DIC:
    - `oro_organization.twig.get_owner.class`
    - `oro_organization.twig.business_units.class`
- The following services were removed:
    - `oro_organization.twig.get_owner`
    - `oro_organization.twig.business_units`
- Class `Oro\Bundle\OrganizationBundle\Twig\BusinessUnitExtension` was removed
- Class `Oro\Bundle\OrganizationBundle\Twig\OwnerTypeExtension` was removed

PlatformBundle
--------------
- The parameter `oro_platform.twig.platform_extension.class` was removed from DIC
- The service `oro_platform.twig.platform_extension` was marked as `private`
- Class `Oro\Bundle\PlatformBundle\Twig\PlatformExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $helper`

ReminderBundle
--------------
- The parameter `oro_reminder.twig.extension.class` was removed from DIC
- The service `oro_reminder.twig.extension` was marked as `private`
- Class `Oro\Bundle\ReminderBundle\Twig\ReminderExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $entityManager`
    - removed property `protected $securityContext`
    - removed property `protected $messageParamsProvider`

RequireJSBundle
---------------
- The service `oro_requirejs.twig.requirejs_extension` was marked as `private`
- Class `Oro\Bundle\RequireJSBundle\Twig\OroRequireJSExtension`
    - the construction signature of was changed. The parameter `ConfigProviderManager $manager` was replaced with `ContainerInterface $container`
    - removed property `protected $manager`

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
- The parameter `oro_search.twig_extension.class` was removed from DIC
- The service `oro_search.twig.search_extension` was marked as `private`
- `Oro\Bundle\SearchBundle\Engine\PdoMysql` `getWords` method is deprecated. All non alphanumeric chars are removed in `Oro\Bundle\SearchBundle\Engine\BaseDriver` `filterTextFieldValue` from fulltext search for MySQL and PgSQL
- The `oro:search:reindex` command now works synchronously by default. Use the `--scheduled` parameter if you need the old, async behaviour

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
    - `oro_security.twig.security_extension.class`
    - `oro_security.twig.security_organization_extension`
    - `oro_security.twig.acl.permission_extension.class`
    - `oro_security.listener.context_listener.class`
    - `oro_security.listener.console_context_listener.class`
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
- Class `Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategy`
    - changed the signature of method `setSecurityMetadataProvider`. The parameter `EntitySecurityMetadataProvider $entitySecurityMetadataProvider` was replaced with `ServiceLink $securityMetadataProviderLink`
    - removed parameter `protected $entitySecurityMetadataProvider`
- Class `Oro\Bundle\SecurityBundle\Annotation\AclListener`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $cacheProvider`
    - removed property `protected $dumper`
- Class `Oro\Bundle\SecurityBundle\EventListener\ConsoleContextListener`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed method `setContainer`
    - removed method `getRegistry`
    - removed method `getSecurityContext`
- The service `oro_security.twig.security_extension` was marked as `private`
- The service `oro_security.twig.security_organization_extension` was removed
- The service `oro_security.twig.acl.permission_extension` was removed
- Class `Oro\Bundle\SecurityBundle\Twig\Acl\PermissionExtension` was removed
- Class `Oro\Bundle\SecurityBundle\Twig\OroSecurityOrganizationExtension` was removed
- Class `Oro\Bundle\SecurityBundle\Twig\OroSecurityExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $securityFacade`
- Interface `Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface`
    - signature of method `getAllowedPermissions` changed, added third argument `string|null aclGroup` default `null`
- Class `Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension`
    - signature of method `getPermissionsForType` changed, added second argument `string|null aclGroup` default `null`


SegmentBundle
-------------
- The parameter `oro_segment.twig.extension.segment.class` was removed from DIC
- The service `oro_segment.twig.extension.segment` was marked as `private`

SidebarBundle
-------------
- The parameter `oro_sidebar.twig.extension.class` was removed from DIC
- The parameter `oro_sidebar.request.handler.class` was removed from DIC
- The service `oro_sidebar.twig.extension` was marked as `private`
- Class `Oro\Bundle\SidebarBundle\Twig\SidebarExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $widgetDefinitionsRegistry`
    - removed property `protected $translator`
    - removed property `protected $assetHelper`

SyncBundle
----------
- The parameter `oro_wamp.twig.class` was removed from DIC
- The service `oro_wamp.twig.sync_extension` was marked as `private`
- The service `oro_sync.twig.content.tags_extension` was removed
- Class `Oro\Bundle\SyncBundle\Twig\ContentTagsExtension` was removed
- Class `Oro\Bundle\SyncBundle\Twig\OroSyncExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $publisher`

TagBundle
---------
- The parameter `oro_tag.twig.tag.extension.class` was removed from DIC
- The service `oro_tag.twig.tag.extension` was marked as `private`
- Class `Oro\Bundle\TagBundle\Twig\TagExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $tagManager`
    - removed property `protected $taggableHelper`

ThemeBundle
-----------
- The parameter `oro_theme.twig.extension.class` was removed from DIC
- The service `oro_theme.twig.extension` was marked as `private`
- Class `Oro\Bundle\ThemeBundle\Twig\ThemeExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $themeRegistry`

LayoutBundle
-----------------
- Class `Oro\Bundle\LayoutBundle\DependencyInjection\CompilerOverrideServiceCompilerPass` was removed

TranslationBundle
-----------------
- Added parameter `ConfigDatabaseChecker $databaseChecker` to the constructor of `Oro\Bundle\TranslationBundle\Translation\OrmTranslationLoader`
- The service `oro_translation.twig.translation.extension` was marked as `private`
- Class `Oro\Bundle\TranslationBundle\Twig\TranslationExtension`
    - the construction signature of was changed. Old signature `$debugTranslator, TranslationsDatagridRouteHelper $translationRouteHelper`. New signature `ContainerInterface $container, $debugTranslator`
    - removed property `protected $translationRouteHelper`
- Added `array $filtersType = []` parameter to the `generate` method, that receives an array of filter types to be applies on the route in order to support 
filters such as `contains` when generating routes
- Class `Oro\Bundle\TranslationBundle\Form\Type\AddLanguageType`
   - The signature of constructor was changed, added:
        - third parameter is instance of `TranslationStatisticProvider`
        - fourth parameter is instance of `TranslatorInterface`
   - Changed parent from type from `locale` to `oro_choice`
- Class `Oro\Bundle\TranslationBundle\Provider\TranslationPackagesProviderExtension`
    - removed constant `PACKAGE_NAME`
    - added constructor
    - added method `public function addPackage(string $packageAlias, string $packageName, string $suffix = '')`
- Updated service definition for `oro_translation.extension.transtation_packages_provider`
    - changed publicity to `false`

UIBundle
--------
- Removed the following parameters from DIC:
    - `oro_ui.twig.sort_by.class`
    - `oro_ui.twig.ceil.class`
    - `oro_ui.twig.extension.class`
    - `oro_ui.twig.mobile.class`
    - `oro_ui.twig.widget.class`
    - `oro_ui.twig.date.class`
    - `oro_ui.twig.regex.class`
    - `oro_ui.twig.skype_button.class`
    - `oro_ui.twig.form.class`
    - `oro_ui.twig.formatter.class`
    - `oro_ui.twig.placeholder.class`
    - `oro_ui.twig.tab.class`
    - `oro_ui.twig.content.class`
    - `oro_ui.twig.url.class`
    - `oro_ui.twig.js_template.class`
    - `oro_ui.twig.merge_recursive.class`
    - `oro_ui.twig.block.class`
    - `oro_ui.twig.html_tag.class`
    - `oro_ui.twig.extension.formatter.class`
    - `oro_ui.view.listener.class`
    - `oro_ui.view.content_provider.listener.class`
- Removed the following services:
    - `oro_ui.twig.sort_by_extension`
    - `oro_ui.twig.ceil_extension`
    - `oro_ui.twig.mobile_extension`
    - `oro_ui.twig.form_extension`
    - `oro_ui.twig.view_extension`
    - `oro_ui.twig.formatter_extension`
    - `oro_ui.twig.widget_extension`
    - `oro_ui.twig.date_extension`
    - `oro_ui.twig.regex_extension`
    - `oro_ui.twig.skype_button_extension`
    - `oro_ui.twig.content_extension`
    - `oro_ui.twig.url_extension`
    - `oro_ui.twig.js_template`
    - `oro_ui.twig.merge_recursive`
    - `oro_ui.twig.block`
- The following services were marked as `private`:
    - `oro_ui.twig.extension.formatter`
    - `oro_ui.twig.tab_extension`
    - `oro_ui.twig.html_tag`
    - `oro_ui.twig.placeholder_extension`
    - `oro_ui.twig.ui_extension`
- The following classes were removed:
    - `Oro\Bundle\UIBundle\Twig\BlockExtension`
    - `Oro\Bundle\UIBundle\Twig\CeilExtension`
    - `Oro\Bundle\UIBundle\Twig\ContentExtension`
    - `Oro\Bundle\UIBundle\Twig\DateExtension`
    - `Oro\Bundle\UIBundle\Twig\FormatterExtension`
    - `Oro\Bundle\UIBundle\Twig\FormExtension`
    - `Oro\Bundle\UIBundle\Twig\JsTemplateExtension`
    - `Oro\Bundle\UIBundle\Twig\MergeRecursiveExtension`
    - `Oro\Bundle\UIBundle\Twig\MobileExtension`
    - `Oro\Bundle\UIBundle\Twig\RegexExtension`
    - `Oro\Bundle\UIBundle\Twig\SkypeButtonExtension`
    - `Oro\Bundle\UIBundle\Twig\SortByExtension`
    - `Oro\Bundle\UIBundle\Twig\UrlExtension`
    - `Oro\Bundle\UIBundle\Twig\ViewExtension`
    - `Oro\Bundle\UIBundle\Twig\WidgetExtension`
- Class `Oro\Bundle\UIBundle\Twig\FormatExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatterManagerLink`
- Class `Oro\Bundle\UIBundle\Twig\HtmlTagExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $htmlTagProvider`
    - removed property `protected $htmlTagHelper`
- Class `Oro\Bundle\UIBundle\Twig\PlaceholderExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed method `setRequest`
    - removed property `protected $environment`
    - removed property `protected $placeholder`
    - removed property `protected $kernelExtension`
    - removed property `protected $request`
- Class `Oro\Bundle\UIBundle\Twig\TabExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $menuExtension`
    - removed property `protected $router`
    - removed property `protected $securityFacade`
    - removed property `protected $translator`
- Class `Oro\Bundle\UIBundle\Twig\UiExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $eventDispatcher`

UserBundle
----------
- Class `Oro\Bundle\UserBundle\Security\ImpersonationAuthenticator`
    - replaced parameter `EntityManager $em` with `ManagerRegistry $doctrine` in the constructor
    - removed property `protected $em`
- Class `Oro\Bundle\UserBundle\EventListener\PasswordChangedSubscriber`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $enumValueProvider`
- The parameter `oro_user.twig.user_extension.class` was removed from DIC
- The service `oro_user.twig.user_extension` was marked as `private`
- Class `Oro\Bundle\UserBundle\Twig\OroUserExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $genderProvider`
    - removed property `protected $securityContext`
- Added Configurable Permission `default` for View and Edit pages of User Role (see [configurable-permissions.md](./src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md))
- Class `Oro\Bundle\UserBundle\Controller\StatusController`
    - renamed method `setCurrentStatus` to `setCurrentStatusAction`
    - renamed method `clearCurrentStatus` to `clearCurrentStatusAction`

WindowsBundle
-------------
- The parameter `oro_windows.twig.extension.class` was removed from DIC
- The service `oro_windows.twig.extension` was marked as `private`
- Class `Oro\Bundle\WindowsBundle\Twig\WindowsExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $windowsStateManagerRegistry`
    - removed property `protected $windowsStateRequestManager`

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
- Added `variable_definitions` to workflow definition
- Class `Oro\Bundle\WorkflowBundle\Model\TransitionAssembler`
    - Changed `assemble` method signature from `assemble(array $configuration, array $definitionsConfiguration, $steps, $attributes)` to `assemble(array $configuration, $steps, $attributes)`, where `$configuration` is now the full workflow configuration
- Class `Oro\Bundle\WorkflowBundle\Model\Workflow`:
    - added `TransitionManager $transitionManager = null` as constructor's 6th parameter
    - added `VariableManager $variableManager = null` as constructor's 7th parameter
- Added new `CONFIGURE` permission for workflows
- Interface `Oro\Bundle\WorkflowBundle\Serializer\Normalizer\AttributeNormalizer`:
    - changed 2nd parameter in method's signature from `Attribute $attribute` to `ParameterInterface $attribute` in next methods:
        - `normalize`
        - `denormalize`
        - `supportsNormalization`
        - `supportsDenormalization`
- Class `Oro\Bundle\WorkflowBundle\Serializer\Normalizer\EntityAttributeNormalizer`:
    - changed 2nd parameter in method's signature from `Attribute $attribute` to `ParameterInterface $attribute` in next methods:
        - `normalize`
        - `denormalize`
        - `supportsNormalization`
        - `supportsDenormalization`
        - `validateAttributeValue`
        - `getEntityManager`
- Class `Oro\Bundle\WorkflowBundle\Serializer\Normalizer\MultipleEntityAttributeNormalizer`:
    - changed 2nd parameter in method's signature from `Attribute $attribute` to `ParameterInterface $attribute` in next methods:
        - `normalize`
        - `denormalize`
        - `supportsNormalization`
        - `supportsDenormalization`
        - `validateAttributeValue`
        - `getEntityManager`
- Class `Oro\Bundle\WorkflowBundle\Serializer\Normalizer\StandardAttributeNormalizer`:
    - changed 2nd parameter in method's signature from `Attribute $attribute` to `ParameterInterface $attribute` in next methods:
        - `normalize`
        - `denormalize`
        - `supportsNormalization`
        - `supportsDenormalization`
        - `normalizeObject`
        - `denormalizeObject`
- Class `Oro\Bundle\WorkflowBundle\Serializer\Normalizer\WorkflowDataNormalizer`:
    - changed 2nd parameter in method's signature from `Attribute $attribute` to `ParameterInterface $attribute` in next methods:
        - `normalizeAttribute`
        - `denormalizeAttribute`
        - `findAttributeNormalizer`
    - added protected method `getVariablesNamesFromConfiguration(array $configuration)`
- Abstract class `Oro\Bundle\WorkflowBundle\Translation\AbstractWorkflowTranslationFieldsIterator`:
    - added protected method `&variableFields(array &$configuration, \ArrayObject $context)`
- Class `Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionEntityListener`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
- The service `oro_workflow.twig.extension.workflow` was marked as `private`
- Class `Oro\Bundle\WorkflowBundle\Twig\WorkflowExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $workflowManager`
- Removed implementation of `Oro\Bundle\CronBundle\Command\CronCommandInterface` from `Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand`.
- Removed implementation of `Oro\Bundle\CronBundle\Command\CronCommandInterface` from `Oro\Bundle\WorkflowBundle\Command\HandleTransitionCronTriggerCommand`.
- Class `\Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor`:
    - Signature of method `translateWorkflowDefinitionFields` changed, now it accept optional boolean parameter `$useKeyAsTranslation`
- Class `\Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper`:
    - added public method `generateDefinitionTranslationKeys`
    - added public method `generateDefinitionTranslations`
    - changed access level from `private` to `public` for method `findValue`

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

CronBundle
-------------------
 - Interface `Oro\Bundle\CronBundle\Command\CronCommandInterface`
    - deprecated method `isActive`

TagBundle
---------
- Class `Oro\Bundle\TagBundle\Grid\AbstractTagsExtension`
    - removed method `isReportOrSegmentGrid`
    - removed method `addReportOrSegmentGridPrefix`
    - added UnsupportedGridPrefixesTrait

TranslationBundle
-----------------
- Class `Oro\Bundle\TranslationBundle\ImportExport\Reader\TranslationReader`
    - signature of constructor was changed. The second argument replaced with `LanguageRepository $languageRepository`

Tree Component
--------------
- `Oro\Component\Tree\Handler\AbstractTreeHandler`:
    - added method `getTreeItemList`

DependencyInjection Component
-----------------------------
- Class `Oro\Component\DependencyInjection\ServiceLinkRegistry` together with
`Oro\Component\DependencyInjection\ServiceLinkRegistryAwareInterface` for injection awareness. Can be used to provide
injection of a collection of services that are registered in system, but there no need to instantiate
all of them on every runtime. The registry has `@service_container` dependency (`Symfony\Component\DependencyInjection\ContainerInterface`)
and uses `Oro\Component\DependencyInjection\ServiceLink` instances internally. It can register public services by `ServiceLinkRegistry::add`
with `service_id` and `alias`. Later service can be resolved from registry by its alias on demand (method `::get($alias)`).
- Class `Oro\Component\DependencyInjection\Compiler\TaggedServiceLinkRegistryCompilerPass` to easily setup a tag by 
which services will be gathered into `Oro\Component\DependencyInjection\ServiceLinkRegistry` and then injected to 
provided service (usually that implements `Oro\Component\DependencyInjection\ServiceLinkRegistryAwareInterface`).
