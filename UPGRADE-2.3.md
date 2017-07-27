UPGRADE FROM 2.2 to 2.3
=======================






IntegrationBundle
-----------------
- Interface `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface` was changed:
  - Methods `getRawHeaders`, `xml`, `getRedirectCount`, `getEffectiveUrl` were completely removed
  - Methods `getContentEncoding`, `getContentLanguage`, `getContentLength`, `getContentLocation`, `getContentDisposition`, `getContentMd5`, `getContentRange`, `getContentType`, `isContentType` were superseded by `getHeader` method 
- Interface `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface` was changed:
  - Method `getXML` was completely removed

- Class `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient` method `getXML` was removed, please use a simple `get` method instead and convert its result to XML
- Class `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse`:
  - Methods `getRawHeaders`, `xml`, `getRedirectCount`, `getEffectiveUrl` were removed, in case you need them just use the construction such as `$response->getSourceResponse()->xml()`
  - Methods `getContentEncoding`, `getContentLanguage`, `getContentLength`, `getContentLocation`, `getContentDisposition`, `getContentMd5`, `getContentRange`, `getContentType`, `isContentType` were removed, but you can get the same values if you use `$response->getHeader('Content-Type')` or `$response->getHeader('Content-MD5')`, for example.
- Class `Oro\Bundle\IntegrationBundle\Manager\TypesRegistry` was changed
  - public method `getIntegrationByType(string $typeName)` was added
- Abstract class `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\BridgeRestClientFactory` and its services `oro_integration.transport.rest.bridge.client_factory`, `oro_integration.transport.rest.bridge.decorated_client_factory` were added
  - construction signature:
     - RestClientFactoryInterface $clientFactory
  - public method `createRestClient(Transport $transportEntity)` was added
  - abstract protected method `getClientBaseUrl(ParameterBag $parameterBag)` was added
  - abstract protected method `getClientOptions(ParameterBag $parameterBag)` was added
- Abstract class `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator\AbstractRestClientDecoratorFactory` was added
  - construction signature:
     - RestClientFactoryInterface $restClientFactory
  - public method `getRestClientFactory()` was added
- Class `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator\LoggerClientDecorator` was added. Implements `RestClientInterface`. Use it for logging client.
  - construction signature:
     - RestClientFactoryInterface $restClientFactory
     - LoggerInterface $logger
- Class `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator\LoggerClientDecoratorFactory` and its service `oro_integration.provider.rest_client.logger_decorator_factory` were added. Implements `LoggerAwareInterface`.
  - public method `createRestClient($baseUrl, array $defaultOptions)` was added
- Class `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator\LoggerClientDecorator` was added. Implements `RestClientInterface`. Add the ability to make additional requests to the server.
  - construction signature:
    - RestClientFactoryInterface $restClientFactory
    - LoggerInterface $logger
    - bool $multipleAttemptsEnabled
    - array $sleepBetweenAttempt
- Class `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator\MultiAttemptsClientDecoratorFactory` and its service `oro_integration.provider.rest_client.multi_attempts_decorator_factory` were added. Implements `LoggerAwareInterface`.
- Class `Oro\Bundle\IntegrationBundle\Provider\TransportCacheClearInterface` was added
  - public method `cacheClear($resource = null))` was added

EmailBundle
-----------
- Class `Oro\Bundle\EmailBundle\Datagrid\MailboxChoiceList`
    - changed the constructor signature: unused parameter `Registry $doctrine` was removed, added parameter `MailboxNameHelper $mailboxNameHelper`
- Class `Oro\Bundle\EmailBundle\Datagrid\OriginFolderFilterProvider`
    - removed constants `EMAIL_ORIGIN` and `EMAIL_MAILBOX`
    - changed the constructor signature: parameter `Registry $doctrine` was replaced with `ManagerRegistry $doctrine`, added parameter `MailboxNameHelper $mailboxNameHelper`
- Class `Oro\Bundle\EmailBundle\EventListener\MailboxAuthorizationListener`
    - changed the constructor signature: parameter `Registry $doctrine` was replaced with `ManagerRegistry $doctrine`
- Class `Oro\Bundle\EmailBundle\Form\Type\EmailOriginFromType`
    - changed the constructor signature: parameter `Registry $doctrine` was replaced with `ManagerRegistry $doctrine`
- Class `Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider`
    - removed method `setSecurityContextLink`
- Class `Oro\Bundle\EmailBundle\Twig\EmailExtension`
    - method `getSecurityFacade` was replaces with `getAuthorizationChecker` and `getTokenAccessor`
- Class `Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber`
    - Changed constructor signature
        - first argument was changed from `Symfony\Component\Security\Core\SecurityContextInterface` to `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface`
- Class ` Oro\Bundle\EmailBundle\Provider\EmailRenderer`, changed constructor signature
    - added eighth argument `Oro\Bundle\EmailBundle\Processor\VariableProcessorRegistry`
- Class `Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory`
    - removed property `fromEmailExpression`
    - method `prepareQuery` renamed to `addFromEmailAddress`
    - removed method `getFromEmailExpression`
- Class `Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository`
    - method `getThreadUniqueRecipients` was marked as deprecated. Use `EmailGridResultHelper::addEmailRecipients` instead
- Class `Oro\Bundle\EmailBundle\EventListener\Datagrid\EmailGridListener`
    - changed the constructor signature: added parameter `EmailGridResultHelper $resultHelper`
- Class `Oro\Bundle\EmailBundle\EventListener\Datagrid\RecentEmailGridListener`
    - changed the constructor signature: parameter `EmailQueryFactory $emailQueryFactory = null` was replaced with `EmailQueryFactory $emailQueryFactory`
- Class `Oro\Bundle\EmailBundle\Model\FolderType`
    - method `outcomingTypes` was marked as deprecated. Use `outgoingTypes` instead
- The performance of following data grids were improved and as result theirs definitions and TWIG templates were significantly changed. The main change is to return only fields required fir the grid, instead return whole entity
    - `base-email-grid`
    - `email-grid`
    - `dashboard-recent-emails-inbox-grid`
    - `dashboard-recent-emails-sent-grid`
    - `dashboard-recent-emails-new-grid`
    - `EmailBundle/Resources/views/Email/Datagrid/Property/contacts.html.twig`
    - `EmailBundle/Resources/views/Email/Datagrid/Property/date.html.twig`
    - `EmailBundle/Resources/views/Email/Datagrid/Property/date_long.html.twig`
    - `EmailBundle/Resources/views/Email/Datagrid/Property/from.html.twig`
    - `EmailBundle/Resources/views/Email/Datagrid/Property/mailbox.html.twig`
    - `EmailBundle/Resources/views/Email/Datagrid/Property/recipients.html.twig`
    - `EmailBundle/Resources/views/Email/Datagrid/Property/subject.html.twig`
    - TWIG macro `wrapTextToTag` was marked as deprecated
- Class `Oro\Bundle\EmailBundle\Twig\EmailExtension`
    - method `getEmailThreadRecipients` was marked as deprecated. Use `EmailGridResultHelper::addEmailRecipients` instead

EntityExtendBundle
------------------
- Class `Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoader` was removed. The `Oro\Component\PhpUtils\ClassLoader` is used instead of it

FormBundle
----------
- Updated jQuery Validation plugin to 1.6.0 version
- Updated TinyMCE to 4.6.* version

ImportExportBundle
------------------
- Added a possibility to change aggregation strategy for a job summary. An aggregator should implement `Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorInterface`
- Added two job summary aggregators:
    - `Oro\Bundle\ImportExportBundle\Job\Context\SimpleContextAggregator`, it summarizes counters by the type from all steps and it is a default aggregator
    - `Oro\Bundle\ImportExportBundle\Job\Context\SelectiveContextAggregator`, it summarizes counters by the type from all steps marked as `add_to_job_summary`
- Class `Oro\Bundle\ImportExportBundle\Job\JobExecutor`
    - changed the constructor signature: added parameter `ContextAggregatorRegistry $contextAggregatorRegistry`
    - added constant `JOB_CONTEXT_AGGREGATOR_TYPE`
- Added trait `Oro\Bundle\ImportExportBundle\Job\Step\AddToJobSummaryStepTrait` that can be used in steps support `add_to_job_summary` parameter.
- Class `Oro\Bundle\ImportExportBundle\Reader\EntityReader`
    - changed the constructor signature: parameter `OwnershipMetadataProvider $ownershipMetadata` was replaced with `OwnershipMetadataProviderInterface $ownershipMetadata`

IntegrationBundle
---------------
- Removed translation label `oro.integration.sync_error_invalid_credentials`
- Removed translation label `oro.integration.progress`
- Updated translation label `oro.integration.sync_error`
- Updated translation label `oro.integration.sync_error_integration_deactivated`

LocaleBundle
------------
- Updated Moment.js to 2.18.* version
- Updated Numeral.js to 2.0.6 version

MigrationBundle
---------------
- Added event `oro_migration.data_fixtures.pre_load` that is raised before data fixtures are loaded
- Added event `oro_migration.data_fixtures.post_load` that is raised after data fixtures are loaded

NavigationBundle
----------------
- Service `oro_navigation.item.pinbar.post_persist_listener` was changed from `doctrine.event_listener` to `doctrine.orm.entity_listener`
    - method `setClassName` was removed.
    - method `postPersist` had additional argument `AbstractPinbarTab $pinbarTab`

NoteBundle
----------
- Added new action `create_note` related class `Oro\Bundle\NoteBundle\Action\CreateNoteAction`

NotificationBundle
------------------
- Entity `Oro\Bundle\NotificationBundle\Model\EmailNotification` became Extend

ReportBundle
------------

- Class Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationProvider was modified to use doctrine cache instead of caching the DatagridConfiguration value in property $configuration
    - public method `setPrefixCacheKey($prefixCacheKey)` was removed
    - public method `setReportCacheManager(Cache $reportCacheManager)` was removed
    - changed the constructor signature:
        - parameter `Doctrine\Common\Cache\Cache $reportCacheManager` was added
        - parameter `$prefixCacheKey $prefixCacheKey` was added

     Before
     ```PHP
        class ReportDatagridConfigurationProvider
        {
            /**
             * @var DatagridConfiguration
             */
            protected $configuration;

            public function getConfiguration($gridName)
            {
                if ($this->configuration === null) {
                    ...
                    $this->configuration = $this->builder->getConfiguration();
                }

                return $this->configuration;
            }
        }
     ```

     After
     ```PHP
        class ReportDatagridConfigurationProvider
        {
            /**
             * Doctrine\Common\Cache\Cache
             */
            protected $reportCacheManager;

            public function getConfiguration($gridName)
            {
                $cacheKey = $this->getCacheKey($gridName);

                if ($this->reportCacheManager->contains($cacheKey)) {
                    $config = $this->reportCacheManager->fetch($cacheKey);
                    $config = unserialize($config);
                } else {
                    $config = $this->prepareConfiguration($gridName);
                    $this->reportCacheManager->save($cacheKey, serialize($config));
                }

                return $config;
            }
        }
     ```

- Class Oro\Bundle\ReportBundle\EventListener\ReportCacheCleanerListener was added. It cleans cache of report grid on postUpdate event of Report entity.


SecurityBundle
--------------
- Class `Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference`
    - made `organizationId` optional
- Added class `Oro\Bundle\SecurityBundle\Owner\OwnershipQueryHelper`
- Class `Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder`
    - removed method `getSecurityContext`
- Class `Oro\Bundle\SecurityBundle\Twig\OroSecurityExtension`
    - method `getSecurityFacade` was replaces with `getAuthorizationChecker` and `getTokenAccessor`
- Interface `Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface`
    - method `isGlobalLevelEntity` was marked ad deprecated, use method `isOrganization` instead
    - method `isLocalLevelEntity` was marked ad deprecated, use method `isBusinessUnit` instead
    - method `isBasicLevelEntity` was marked ad deprecated, use method `isUser` instead
    - method `isAssociatedWithGlobalLevelEntity` was marked ad deprecated, use method `isAssociatedWithOrganization` instead
    - method `isAssociatedWithLocalLevelEntity` was marked ad deprecated, use method `isAssociatedWithBusinessUnit` instead
    - method `isAssociatedWithBasicLevelEntity` was marked ad deprecated, use method `isAssociatedWithUser` instead
- Class `Oro\Bundle\SecurityBundle\Acl\Extension\AbstractAccessLevelAclExtension`
    - changed the constructor signature: parameter `MetadataProviderInterface $metadataProvider` was replaced with `OwnershipMetadataProviderInterface $metadataProvider`
- Class `Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension`
    - changed the constructor signature: parameter `MetadataProviderInterface $metadataProvider` was replaced with `OwnershipMetadataProviderInterface $metadataProvider`
- Class `Oro\Bundle\SecurityBundle\Acl\Extension\FieldAclExtension`
    - changed the constructor signature: parameter `MetadataProviderInterface $metadataProvider` was replaced with `OwnershipMetadataProviderInterface $metadataProvider`
- Class `Oro\Bundle\SecurityBundle\Cache\OwnershipMetadataCacheClearer`
    - changed the constructor signature: parameter `MetadataProviderInterface $provider` was replaced with `OwnershipMetadataProviderInterface $provider`
- Class `Oro\Bundle\SecurityBundle\Cache\OwnershipMetadataCacheWarmer`
    - changed the constructor signature: parameter `MetadataProviderInterface $provider` was replaced with `OwnershipMetadataProviderInterface $provider`
- Class `Oro\Bundle\SecurityBundle\EventListener\OwnershipConfigListener`
    - changed the constructor signature: parameter `MetadataProviderInterface $provider` was replaced with `OwnershipMetadataProviderInterface $provider`
- Class `Oro\Bundle\SecurityBundle\EventListener\OwnerTreeListener`
    - removed property `container`
    - removed method `setContainer`
    - removed method `getTreeProvider`
    - changed the constructor signature: new signature is `__construct(OwnerTreeProviderInterface $treeProvider)`
- Class `Oro\Bundle\SecurityBundle\EventListener\SearchListener`
    - changed the constructor signature: parameter `OwnershipMetadataProvider $metadataProvider` was replaced with `OwnershipMetadataProviderInterface $metadataProvider`
- Class `Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder`
    - changed the constructor signature: parameter `MetadataProviderInterface $metadataProvider` was replaced with `OwnershipMetadataProviderInterface $metadataProvider`
    - removed deprecated method `fillOrganizationBusinessUnitIds`
    - removed deprecated method `fillOrganizationUserIds`
- Interface `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface`
    - method `isBasicLevelOwned` was marked ad deprecated, use method `isUserOwned` instead
    - method `isLocalLevelOwned` was marked ad deprecated, use method `isBusinessUnitOwned` instead
    - method `isGlobalLevelOwned` was marked ad deprecated, use method `isOrganizationOwned` instead
    - method `isSystemLevelOwned` was marked ad deprecated
    - method `getGlobalOwnerColumnName` was marked ad deprecated, use method `getOrganizationColumnName` instead
    - method `getGlobalOwnerFieldName` was marked ad deprecated, use method `getOrganizationFieldName` instead
- Interface `Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface` was renamed to `OwnershipMetadataProviderInterface`
    - method `getBasicLevelClass` was marked ad deprecated, use method `getUserClass` instead
    - method `getLocalLevelClass` was marked ad deprecated, use method `getBusinessUnitClass` instead
    - method `getGlobalLevelClass` was marked ad deprecated, use method `getOrganizationClass` instead
- Class `Oro\Bundle\SecurityBundle\Owner\Metadata\AbstractMetadataProvider` was renamed to `AbstractOwnershipMetadataProvider`
    - changed the constructor signature: old signature was `__construct(array $owningEntityNames)`, new signature is `__construct(ConfigManager $configManager)`
    - removed property `localCache`
    - removed property `owningEntityNames`
    - removed method `setContainer`
    - removed method `getContainer`
    - removed method `getConfigProvider`
    - removed method `getEntityClassResolver`
    - removed method `setAccessLevelClasses`
- Class `Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider` was renamed to `ChainOwnershipMetadataProvider`
- Class `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider`
    - removed property `configProvider`
    - removed property `organizationClass`
    - removed property `businessUnitClass`
    - removed property `userClass`
    - changed the constructor signature: new signature is `__construct(array $owningEntityNames, ConfigManager $configManager, EntityClassResolver $entityClassResolver, TokenAccessorInterface $tokenAccessor, CacheProvider $cache)`
- Interface `Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface` was renamed to `OwnerTreeBuilderInterface`
    - method `addBasicEntity` was marked ad deprecated, use method `addUser` instead
    - method `addGlobalEntity` was marked ad deprecated, use method `addUserOrganization` instead
    - method `addLocalEntityToBasic` was marked ad deprecated, use method `addUserBusinessUnit` instead
    - method `addDeepEntity` was marked ad deprecated, use method `addBusinessUnitRelation` instead
    - method `addLocalEntity` was marked ad deprecated, use method `addBusinessUnit` instead
- Added new interface `Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface`
- Class `Oro\Bundle\SecurityBundle\Owner\AbstractEntityOwnershipDecisionMaker`
    - changed the constructor signature: new signature is `__construct(OwnerTreeProviderInterface $treeProvider, ObjectIdAccessor $objectIdAccessor, EntityOwnerAccessor $entityOwnerAccessor, OwnershipMetadataProviderInterface $ownershipMetadataProvider)`
    - removed method `getMetadataProvider`
    - removed method `getTreeProvider`
    - removed method `getObjectIdAccessor`
    - removed method `getEntityOwnerAccessor`
    - removed method `setContainer`
    - removed method `getContainer`
- Class `Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor`
    - changed the constructor signature: parameter `MetadataProviderInterface $metadataProvider` was replaced with `OwnershipMetadataProviderInterface $metadataProvider`
- Class `Oro\Bundle\SecurityBundle\Owner\EntityOwnershipDecisionMaker`
    - changed the constructor signature: new signature is `__construct(OwnerTreeProviderInterface $treeProvider, ObjectIdAccessor $objectIdAccessor, EntityOwnerAccessor $entityOwnerAccessor, OwnershipMetadataProviderInterface $ownershipMetadataProvider, TokenAccessorInterface $tokenAccessor)`
- Class `Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider`
    - changed the constructor signature: parameter `MetadataProviderInterface $ownershipMetadataProvider` was replaced with `OwnershipMetadataProviderInterface $ownershipMetadataProvider`
- Removed DI container parameter `oro_security.owner.tree.class`
- Removed DI container parameter `oro_security.owner.decision_maker.abstract.class`
- Removed service `oro_security.owner.tree`
- Removed service `oro_security.owner.decision_maker.abstract`
- Removed service `oro_security.link.ownership_tree_provider`

SearchBundle
------------
- Class `Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataListener` was replaced with `Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataFixturesListener`
- Service `oro_search.event_listener.reindex_demo_data` was replaced with `oro_search.migration.demo_data_fixtures_listener.reindex`

UIBundle
--------
- Updated ChaplinJS to 1.2.0 version
- Updated Autolinker.js to 1.4.* version
- Updated jQuery-Form to 4.2.1 version
- Updated jQuery.Numeric to 1.5.0 version
- Updated Lightgallery.js to 1.4.0 version
- Updated RequireJS test.js plugin to 2.0.* version
- Updated Jquery-UI-Multiselect-Widget to 2.0.1 version
- Updated Timepicker.js plugin to 1.11.* version
- Updated Datepair.js plugin to 0.4.* version

TestFrameworkBundle
-------------------
- Class `TestListener` namespace added, use `Oro\Bundle\TestFrameworkBundle\Test\TestListener` instead
- Removed `--applicable-suites` parameter from behat.
Now every bundle should provide only features that applicable to any application that include that bundle.

TranslationBundle
-----------------
- Class `Oro\Bundle\TranslationBundle\Provider\PackagesProvider`
    - property `pmLink` was replaced with `pm`
    - changed the constructor signature: parameter `ServiceLink $pmLink` was replaced with `PackageManager $pm`
- Removed service `oro_translation.distribution.package_manager.link`

WorkflowBundle
--------------
- Class `Oro\Bundle\WorkflowBundle\EventListener\Extension\ProcessTriggerExtension`
    - removed property `$queuedJobs`
    - changed signature of method `createJobs`. Added parameter `$queuedJobs`
- Class `Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry`:
    - changed constructor signature:
        - first argument replaced with `Oro\Bundle\WorkflowBundle\Provider\WorkflowDefinitionProvider $definitionProvider`;
    - following protected methods were moved to `WorkflowDefinitionProvider`:
        - `refreshWorkflowDefinition`
        - `getEntityManager`
        - `getEntityRepository`
- Added provider `oro_workflow.provider.workflow_definition` to manage cached instances of `WorkflowDefinitions`.
- Added cache provider `oro_workflow.cache.provider.workflow_definition` to hold cached instances of `WorkflowDefinitions`.
- Class `Oro\Bundle\WorkflowBundle\Acl\Extension\AbstractWorkflowAclExtension`
    - changed the constructor signature: parameter `MetadataProviderInterface $metadataProvider` was replaced with `OwnershipMetadataProviderInterface $metadataProvider`
- Class `Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclExtension`
    - changed the constructor signature: parameter `MetadataProviderInterface $metadataProvider` was replaced with `OwnershipMetadataProviderInterface $metadataProvider`
- Class `Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowTransitionAclExtension`
    - changed the constructor signature: parameter `MetadataProviderInterface $metadataProvider` was replaced with `OwnershipMetadataProviderInterface $metadataProvider`
- Added Datagrid filter `Oro\Bundle\WorkflowBundle\Datagrid\Filter\WorkflowTranslationFilter`
- Updated Datagrid filter `Oro\Bundle\WorkflowBundle\Datagrid\Filter\WorkflowFilter`
    - changed namespace
- Class `Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider`:
    - it does not extend `Oro\Bundle\WorkflowBundle\Configuration\AbstractConfigurationProvider` anymore
    - completely changed signature of constructor, now it accepts four parameters:
        - `Oro\Bundle\WorkflowBundle\Configuration\WorkflowListConfiguration $configuration`
        - `Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder $finderBuilder`
        - `Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface $reader`
        - `Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationImportsProcessor $configurationImportsProcessor`
    - removed next methods:
        - `protected function loadConfigFile(\SplFileInfo $file)`
        - `protected function processImports(\SplFileInfo $file, array $imports, array $configData)`
        - `protected function applyWorkflowImports(array $configData, $recipient, array &$imports, \SplFileInfo $sourceFile)`
        - `protected function getConfigFilePattern()`
- Removed service container parameters:
    - `oro_workflow.configuration.config.workflow_sole.class`
    - `oro_workflow.configuration.config.workflow_list.class`
    - `oro_workflow.configuration.handler.step.class`
    - `oro_workflow.configuration.handler.attribute.class`
    - `oro_workflow.configuration.handler.transition.class`
    - `oro_workflow.configuration.handler.workflow.class`
    - `oro_workflow.configuration.config.process_definition_sole.class`
    - `oro_workflow.configuration.config.process_definition_list.class`
    - `oro_workflow.configuration.config.process_trigger_sole.class`
    - `oro_workflow.configuration.config.process_trigger_list.class`
    - `oro_workflow.configuration.provider.workflow_config.class`
    - `oro_workflow.configuration.provider.process_config.class`
    - `oro_workflow.configuration.builder.workflow_definition.class`
    - `oro_workflow.configuration.builder.workflow_definition.handle.class`
    - `oro_workflow.configuration.builder.process_configuration.class`

UIBundle
--------
- Updated ChaplinJS to 1.2.0 version
- Updated Autolinker.js to 1.4.* version
- Updated jQuery-Form to 4.2.1 version
- Updated jQuery.Numeric to 1.5.0 version
- Updated Lightgallery.js to 1.4.0 version
- Updated RequireJS test.js plugin to 2.0.* version
- Updated jQuery.Uniform to 4.2.0 version

LocaleBundle
------------
- Updated Moment.js to 2.18.* version
- Updated Numeral.js to 2.0.6 version
