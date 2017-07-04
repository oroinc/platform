UPGRADE FROM 2.2 to 2.3
=======================

**IMPORTANT**
-------------

The class `Oro\Bundle\SecurityBundle\SecurityFacade`, services `oro_security.security_facade` and `oro_security.security_facade.link`, and TWIG function `resource_granted` were marked as deprecated.
Use services `security.authorization_checker`, `security.token_storage`, `oro_security.token_accessor`, `oro_security.class_authorization_checker`, `oro_security.request_authorization_checker` and TWIG function `is_granted` instead.
In controllers use `isGranted` method from `Symfony\Bundle\FrameworkBundle\Controller\Controller`.
The usage of deprecated service `security.context` (interface `Symfony\Component\Security\Core\SecurityContextInterface`) was removed as well.
All existing classes were updated to use new services instead of the `SecurityFacade` and `SecurityContext`:

- service `security.authorization_checker`
    - implements `Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface`
    - the property name in classes that use this service is `authorizationChecker`
- service `security.token_storage`
    - implements `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface`
    - the property name in classes that use this service is `tokenStorage`
- service `oro_security.token_accessor`
    - implements `Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface`
    - the property name in classes that use this service is `tokenAccessor`
- service `oro_security.class_authorization_checker`
    - implements `Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker`
    - the property name in classes that use this service is `classAuthorizationChecker`
- service `oro_security.request_authorization_checker`
    - implements `Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker`
    - the property name in classes that use this service is `requestAuthorizationChecker`

Action component
----------------
- Class `Oro\Component\Action\Action\AssignActiveUser`
    - changed the constructor signature: parameter `SecurityContextInterface $securityContext` was replaced with `TokenStorageInterface $tokenStorage`
    - property `securityContext` was replaced with `tokenStorage`

PhpUtils component
------------------
- Removed deprecated class `Oro\Component\PhpUtils\QueryUtil`. Use `Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil` instead
- Added class `Oro\Component\PhpUtils\ClassLoader`, it is a simple and fast implementation of the class loader that can be used to map one namespace to one path

DoctrineUtils component
-----------------------
- Class `Oro\Component\DoctrineUtils\ORM\QueryUtils` was marked as deprecated. Its methods were moved to 4 classes:
    - `Oro\Component\DoctrineUtils\ORM\QueryUtil`
    - `Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil`
    - `Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil`
    - `Oro\Component\DoctrineUtils\ORM\DqlUtil`

ActivityBundle
--------------
- Class `Oro\Bundle\ActivityBundle\Entity\Manager\ActivityEntityDeleteHandler`
    - method `setSecurityFacade` was replaced with `setAuthorizationChecker`

ApiBundle
---------
- Class `Oro\Bundle\ApiBundle\Processor\Config\GetConfig\AddOwnerValidator`
    - changed the constructor signature: parameter `OwnershipMetadataProvider $ownershipMetadataProvider` was replaced with `OwnershipMetadataProviderInterface $ownershipMetadataProvider`

DashboardBundle
---------------
- Class `Oro\Bundle\DashboardBundle\Controller\DashboardController`
    - removed method `getSecurityFacade`

DataGridBundle
--------------
- Removed class `Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\ActionProvidersPass`
- Removed class `Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\MassActionsPass`
- Class `Oro\Bundle\DataGridBundle\Extension\FieldAcl\FieldAclExtension`
    - removed constant `OWNER_FIELD_PLACEHOLDER`
    - removed constant `ORGANIZARION_FIELD_PLACEHOLDER`
    - removed property `$ownershipMetadataProvider`
    - removed property `$entityClassResolver`
    - removed property `$configProvider`
    - removed property `$queryAliases`
    - changed constructor signature from `__construct(OwnershipMetadataProvider $ownershipMetadataProvider, EntityClassResolver $entityClassResolver, AuthorizationCheckerInterface $authorizationChecker, ConfigProvider $configProvider)` to `__construct(AuthorizationCheckerInterface $authorizationChecker, ConfigManager $configManager, OwnershipQueryHelper $ownershipQueryHelper)`
    - removed method `collectEntityAliases`
    - removed method `addIdentitySelectsToQuery`
    - removed method `tryToGetAliasFromSelectPart`
- Removed service `oro_datagrid.extension.action.abstract`
- Added class `Oro\Bundle\DataGridBundle\Extension\Action\ActionFactory`
- Added class `Oro\Bundle\DataGridBundle\Extension\Action\ActionMetadataFactory`
- Class `Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension`
    - removed constant `ACTION_TYPE_KEY`
    - removed property `$container`
    - removed property `$translator`
    - removed property `$actions`
    - removed property `$excludeParams`
    - changed constructor signature from `__construct(ContainerInterface $container, SecurityFacade $securityFacade, TranslatorInterface $translator)` to `__construct(ActionFactory $actionFactory, ActionMetadataFactory $actionMetadataFactory, SecurityFacade $securityFacade, OwnershipQueryHelper $ownershipQueryHelper)`
    - removed method `registerAction`. Use `Oro\Bundle\DataGridBundle\Extension\Action\ActionFactory::registerAction` instead
    - removed method `getApplicableActionProviders`
    - removed method `getActionObject`
    - removed method `create`
    - removed method `isResourceGranted`
- Added class `Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionFactory`
- Added class `Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionMetadataFactory`
- Class `Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionExtension`
    - removed inheritance from `Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension`
    - removed property `$actions`
    - changed constructor signature from `__construct(ContainerInterface $container, SecurityFacade $securityFacade, TranslatorInterface $translator)` to `__construct(MassActionFactory $actionFactory, MassActionMetadataFactory $actionMetadataFactory, SecurityFacade $securityFacade)`
    - removed method `registerAction`. Use `Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionFactory::registerAction` instead
- Class `Oro\Bundle\DataGridBundle\Controller\Api\Rest\GridViewController`
    - removed method `getSecurityFacade`
- Class `Oro\Bundle\DataGridBundle\EventListener\GridViewsLoadListener`
    - changed the constructor signature: parameter `Registry $registry` was replaced with `ManagerRegistry $registry`
    - removed method `getCurrentUser`
- Class `Oro\Bundle\DataGridBundle\Extension\Columns\ColumnsExtension`
    - changed the constructor signature: parameter `Registry $registry` was replaced with `ManagerRegistry $registry`
    - removed method `getCurrentUser`
- Class `Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension`
    - removed method `getCurrentUser`
- Class `Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditingExtension`
    - property `authChecker` was renamed to `authorizationChecker`
    - method `getColummFieldName` was renamed to `getColumnFieldName`
- Class `Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler`
    - changed the constructor signature: parameter `RegistryInterface $registry` was replaced with `ManagerRegistry $registry`
- Class `Oro\Bundle\DataGridBundle\Twig\DataGridExtension`
    - method `getSecurityFacade` was replaced with `getAuthorizationChecker`
- Class `Oro\Bundle\DataGridBundle\Extension\Pager\AbstractPagerExtension`
    - changed the constructor signature: `AbstractPager $pager` replaced on `PagerInterface $pager`
- Interface `Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface`
    - method `init` was removed

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

CronBundle
----------
- Class `Oro\Bundle\CronBundle\Async\CommandRunnerMessageProcessor`
    - removed property `$commandRunner`
    - changed constructor signature from `__construct(CommandRunnerInterface $commandRunner, JobRunner $jobRunner, LoggerInterface $logger)` to `__construct(JobRunner $jobRunner, LoggerInterface $logger, MessageProducerInterface $producer)`
- Added class `Oro\Bundle\CronBundle\Async\CommandRunnerProcessor`

EmailBundle
-----------
- Class `Oro\Bundle\EmailBundle\Datagrid\MailboxChoiceList`
    - changed the constructor signature: unused parameter `Registry $doctrine` was removed
- Class `Oro\Bundle\EmailBundle\Datagrid\OriginFolderFilterProvider`
    - changed the constructor signature: parameter `Registry $doctrine` was replaced with `ManagerRegistry $doctrine`
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

EntityConfigBundle
------------------
 - Class `Oro\Bundle\EntityConfigBundle\EventListener\AttributeFormViewListener`
    - method `onFormRender` was removed
    - method `onViewRender` was removed

EntityBundle
------------
- Class `Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldManager`
    - changed the constructor signature: parameter `OwnershipMetadataProvider $ownershipMetadataProvider` was replaced with `OwnershipMetadataProviderInterface $ownershipMetadataProvider`

EntityExtendBundle
------------------
- Class `Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoader` was removed. The `Oro\Component\PhpUtils\ClassLoader` is used instead of it
- Class `Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension`
    - method `getSecurityFacade` was replaces with `getAuthorizationChecker`

EntityPaginationBundle
----------------------
- Class `Oro\Bundle\EntityPaginationBundle\Storage\StorageDataCollector`
    - removed property `aclHelper`
    - changed constructor signature: removed parameter `AclHelper $aclHelper`

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
- Class `Oro\Bundle\IntegrationBundle\Provider\AbstractSyncProcessor`
    - added method `dispatchSyncEvent`
- Class `Oro\Bundle\IntegrationBundle\Provider\SyncProcessor`
    - changed method `processImport` signature from `processImport(ConnectorInterface $connector, $jobName, $configuration, Integration $integration)` to `processImport(Integration $integration, ConnectorInterface $connector, array $configuration)`
- Class `Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor`
    - changed the constructor signature: added parameter `ManagerRegistry $doctrineRegistry`
    - changed method `processExport` signature from `processExport($jobName, array $configuration)` to `processExport(Integration $integration, ConnectorInterface $connector, array $configuration)`
- Class `Oro\Bundle\IntegrationBundle\Controller\IntegrationController`
    - removed method `getSyncScheduler`
    - removed method `getTypeRegistry`
    - removed method `getLogger`
- Removed translation label `oro.integration.sync_error_invalid_credentials`
- Removed translation label `oro.integration.progress`
- Updated translation label `oro.integration.sync_error`
- Updated translation label `oro.integration.sync_error_integration_deactivated`
- Class `Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper`
    - changed the constructor signature: parameter `OwnershipMetadataProvider $ownershipMetadataProvider` was replaced with `OwnershipMetadataProviderInterface $ownershipMetadataProvider`

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
- Class `Oro\Bundle\NavigationBundle\Menu\NavigationHistoryBuilder`
    - method `setOptions` was renamed to `setConfigManager`
- Class `Oro\Bundle\NavigationBundle\Menu\NavigationItemBuilder`
    - changed the constructor signature: parameter `Router $router` was replaced with `RouterInterface $router`
- Class `Oro\Bundle\NavigationBundle\Menu\NavigationMostviewedBuilder`
    - method `setOptions` was renamed to `setConfigManager`
- Service `oro_navigation.item.pinbar.post_persist_listener` was changed from `doctrine.event_listener` to `doctrine.orm.entity_listener`
    - method `setClassName` was removed.
    - method `postPersist` had additional argument `AbstractPinbarTab $pinbarTab`

NoteBundle
----------
- Added new action `create_note` related class `Oro\Bundle\NoteBundle\Action\CreateNoteAction`

NotificationBundle
------------------
- Entity `Oro\Bundle\NotificationBundle\Model\EmailNotification` became Extend
- Updated Class `Oro\Bundle\NotificationBundle\Form\Handler\EmailNotificationHandler` 
    - implements `Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface`
    - constructor signature changed, accepts only one argument instance of `Doctrine\Common\Persistence\ManagerRegistry`
    - signature of method `process` changed, now it accepts:
        - `mixed` $data
        - `FormInterface` $form
        - `Request` $request

OrganizationBundle
------------------
- Class `Oro\Bundle\OrganizationBundle\Autocomplete\BusinessUnitTreeSearchHandler`
    - method `setSecurityFacade` was replaced with `setTokenAccessor`
- Class `Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager`
    - removed method `getOrganizationContextId`
- Class `Oro\Bundle\OrganizationBundle\Event\BusinessUnitGridListener`
    - removed method `getSecurityContext`
- Class `Oro\Bundle\OrganizationBundle\EventListener\RecordOwnerDataListener`
    - removed method `getSecurityContext`
- Class `Oro\Bundle\OrganizationBundle\Provider\Filter\ChoiceTreeBusinessUnitProvider`
    - removed method `getUser`
- Class `Oro\Bundle\OrganizationBundle\Form\Extension\OwnerFormExtension`
    - changed the constructor signature: parameter `OwnershipMetadataProvider $ownershipMetadataProvider` was replaced with `OwnershipMetadataProviderInterface $ownershipMetadataProvider`
- Class `Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager`
    - changed the constructor signature: parameter `OwnershipMetadataProvider $ownershipMetadata` was replaced with `OwnershipMetadataProviderInterface $ownershipMetadata`
- Class `Oro\Bundle\OrganizationBundle\Tools\OwnershipEntityConfigDumperExtension`
    - changed the constructor signature: parameter `OwnershipMetadataProvider $ownershipMetadataProvider` was replaced with `OwnershipMetadataProviderInterface $ownershipMetadataProvider`
- Class `Oro\Bundle\OrganizationBundle\Validator\Constraints\OrganizationUniqueEntityValidator`
    - changed the constructor signature: parameter `OwnershipMetadataProvider $metadataProvider` was replaced with `OwnershipMetadataProviderInterface $metadataProvider`
- Class `Oro\Bundle\OrganizationBundle\Validator\Constraints\OwnerValidator`
    - changed the constructor signature: parameter `OwnershipMetadataProvider $ownershipMetadataProvider` was replaced with `OwnershipMetadataProviderInterface $ownershipMetadataProvider`

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

SegmentBundle
-------------
- Class `Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager`
    - changed the constructor signature: added parameter `SubQueryLimitHelper $subQueryLimitHelper`
- Class `Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager`
    - changed the constructor signature: parameter `OwnershipMetadataProvider $ownershipMetadataProvider` was replaced with `OwnershipMetadataProviderInterface $ownershipMetadataProvider`

SearchBundle
------------
- Class `Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataListener` was replaced with `Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataFixturesListener`
- Service `oro_search.event_listener.reindex_demo_data` was replaced with `oro_search.migration.demo_data_fixtures_listener.reindex`

TagBundle
---------
- Class `Oro\Bundle\TagBundle\Grid\TagsExtension`
    - removed method `isAccessGranted`
- Class `Oro\Bundle\UIBundle\Twig\TabExtension`
    - method `getSecurityFacade` was replaces with `getAuthorizationChecker`

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

UserBundle
----------
- Class `Oro\Bundle\UserBundle\Autocomplete\OrganizationUsersHandler`
    - method `setSecurityFacade` was replaces with `setTokenAccessor`
- Class `Oro\Bundle\UserBundle\Autocomplete\UserAclHandler`
    - removed method `getSecurityContext`
- Class `Oro\Bundle\UserBundle\Autocomplete\WidgetUserSearchHandler`
    - method `setSecurityFacade` was replaces with `setTokenAccessor`
- Class `Oro\Bundle\UserBundle\Dashboard\Converters\WidgetUserSelectConverter`
    - method `setSecurityFacade` was replaces with `setTokenAccessor`
- Class `Oro\Bundle\UserBundle\EventListener\OwnerUserGridListener`
    - removed method `getSecurityContext`
- Class `Oro\Bundle\UserBundle\Form\EventListener\UserImapConfigSubscriber`
    - changed the constructor signature: parameter `ObjectManager $manager` was replaced with `EntityManager $entityManager`
    - property `manager` was renamed to `entityManager`
- Class `Oro\Bundle\UserBundle\Handler\UserDeleteHandler`
    - method `setSecurityFacade` was replaces with `setTokenAccessor`
- Class `Oro\Bundle\UserBundle\Menu\UserMenuBuilder`
    - changed the constructor signature: unused parameter `SecurityContextInterface $securityContext` was removed

TestFrameworkBundle
-------------------
- Class `TestListener` namespace added, use `Oro\Bundle\TestFrameworkBundle\Test\TestListener` instead

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

LocaleBundle
------------
- Updated Moment.js to 2.18.* version
- Updated Numeral.js to 2.0.6 version
