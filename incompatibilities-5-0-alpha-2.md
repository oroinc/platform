- [AttachmentBundle](#attachmentbundle)
- [ConfigBundle](#configbundle)
- [DataAuditBundle](#dataauditbundle)
- [DataGridBundle](#datagridbundle)
- [EmailBundle](#emailbundle)
- [EmbeddedFormBundle](#embeddedformbundle)
- [EntityBundle](#entitybundle)
- [EntityExtendBundle](#entityextendbundle)
- [FilterBundle](#filterbundle)
- [GoogleIntegrationBundle](#googleintegrationbundle)
- [ImapBundle](#imapbundle)
- [InstallerBundle](#installerbundle)
- [LayoutBundle](#layoutbundle)
- [LocaleBundle](#localebundle)
- [NotificationBundle](#notificationbundle)
- [SearchBundle](#searchbundle)
- [SecurityBundle](#securitybundle)
- [SyncBundle](#syncbundle)
- [TestUtils](#testutils)
- [WorkflowBundle](#workflowbundle)

AttachmentBundle
----------------
* The `FileController::getFileAction($id, $filename, $action)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/AttachmentBundle/Controller/FileController.php#L32 "Oro\Bundle\AttachmentBundle\Controller\FileController")</sup> method was changed to `FileController::getFileAction($id, $filename, $action, Request $request)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/AttachmentBundle/Controller/FileController.php#L33 "Oro\Bundle\AttachmentBundle\Controller\FileController")</sup>

ConfigBundle
------------
* The `ConfigRepository::deleteByEntity`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/ConfigBundle/Entity/Repository/ConfigRepository.php#L33 "Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository::deleteByEntity")</sup> method was removed.

DataAuditBundle
---------------
* The `SendChangedEntitiesToMessageQueueListener::setEnabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/DataAuditBundle/EventListener/SendChangedEntitiesToMessageQueueListener.php#L120 "Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener::setEnabled")</sup> method was removed.

DataGridBundle
--------------
* The `RequestParameterBagFactory::fetchParameters($gridParameterName)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/DataGridBundle/Datagrid/RequestParameterBagFactory.php#L36 "Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory")</sup> method was changed to `RequestParameterBagFactory::fetchParameters($gridParameterName)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/DataGridBundle/Datagrid/RequestParameterBagFactory.php#L40 "Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory")</sup>

EmailBundle
-----------
* The `AggregatedEmailTemplatesSender::send($entity, $recipients, $from, $templateName)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EmailBundle/Tools/AggregatedEmailTemplatesSender.php#L64 "Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender")</sup> method was changed to `AggregatedEmailTemplatesSender::send($entity, $recipients, $from, $templateName, $templateParams = [])`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/EmailBundle/Tools/AggregatedEmailTemplatesSender.php#L65 "Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender")</sup>
* The `EntityListener::setEnabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EmailBundle/EventListener/EntityListener.php#L82 "Oro\Bundle\EmailBundle\EventListener\EntityListener::setEnabled")</sup> method was removed.
* The `EntityListener::$enabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EmailBundle/EventListener/EntityListener.php#L36 "Oro\Bundle\EmailBundle\EventListener\EntityListener::$enabled")</sup> property was removed.

EmbeddedFormBundle
------------------
* The `EmbeddedFormController::defaultDataAction($formType)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EmbeddedFormBundle/Controller/EmbeddedFormController.php#L73 "Oro\Bundle\EmbeddedFormBundle\Controller\EmbeddedFormController")</sup> method was changed to `EmbeddedFormController::defaultDataAction($formType)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/EmbeddedFormBundle/Controller/EmbeddedFormController.php#L73 "Oro\Bundle\EmbeddedFormBundle\Controller\EmbeddedFormController")</sup>

EntityBundle
------------
* The `ModifyCreatedAndUpdatedPropertiesListener::setEnabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EntityBundle/EventListener/ModifyCreatedAndUpdatedPropertiesListener.php#L35 "Oro\Bundle\EntityBundle\EventListener\ModifyCreatedAndUpdatedPropertiesListener::setEnabled")</sup> method was removed.
* The `ModifyCreatedAndUpdatedPropertiesListener::$enabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EntityBundle/EventListener/ModifyCreatedAndUpdatedPropertiesListener.php#L22 "Oro\Bundle\EntityBundle\EventListener\ModifyCreatedAndUpdatedPropertiesListener::$enabled")</sup> property was removed.
* The `DictionaryApiEntityManager::__construct(ObjectManager $om, ChainDictionaryValueListProvider $dictionaryProvider, ConfigManager $entityConfigManager, EntityNameResolver $entityNameResolver)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EntityBundle/Entity/Manager/DictionaryApiEntityManager.php#L45 "Oro\Bundle\EntityBundle\Entity\Manager\DictionaryApiEntityManager")</sup> method was changed to `DictionaryApiEntityManager::__construct(ObjectManager $om, ChainDictionaryValueListProvider $dictionaryProvider, ConfigManager $entityConfigManager, EntityNameResolver $entityNameResolver, AclHelper $aclHelper)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/EntityBundle/Entity/Manager/DictionaryApiEntityManager.php#L50 "Oro\Bundle\EntityBundle\Entity\Manager\DictionaryApiEntityManager")</sup>

EntityExtendBundle
------------------
* The following methods in class `ExtendFieldValidationLoader`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/EntityExtendBundle/Validator/ExtendFieldValidationLoader.php#L23 "Oro\Bundle\EntityExtendBundle\Validator\ExtendFieldValidationLoader")</sup> were changed:
  > - `__construct(ConfigProvider $extendConfigProvider, ConfigProvider $fieldConfigProvider)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EntityExtendBundle/Validator/ExtendFieldValidationLoader.php#L24 "Oro\Bundle\EntityExtendBundle\Validator\ExtendFieldValidationLoader")</sup>
  > - `__construct(ConfigProvider $extendConfigProvider, ConfigProvider $fieldConfigProvider, FieldConfigConstraintsFactory $fieldConfigConstraintsFactory)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/EntityExtendBundle/Validator/ExtendFieldValidationLoader.php#L23 "Oro\Bundle\EntityExtendBundle\Validator\ExtendFieldValidationLoader")</sup>

  > - `isApplicable($className, $fieldName)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EntityExtendBundle/Validator/ExtendFieldValidationLoader.php#L63 "Oro\Bundle\EntityExtendBundle\Validator\ExtendFieldValidationLoader")</sup>
  > - `isApplicable(ConfigInterface $extendConfig)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/EntityExtendBundle/Validator/ExtendFieldValidationLoader.php#L69 "Oro\Bundle\EntityExtendBundle\Validator\ExtendFieldValidationLoader")</sup>

* The `EnumTranslationCache::__construct(TranslatorInterface $translator, Cache $cache)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EntityExtendBundle/Cache/EnumTranslationCache.php#L27 "Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache")</sup> method was changed to `EnumTranslationCache::__construct(Cache $cache, LocalizationHelper $localizationHelper, LocaleSettings $localeSettings)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/EntityExtendBundle/Cache/EnumTranslationCache.php#L30 "Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache")</sup>
* The `ExtendFieldTypeGuesser::addConstraintsToOptions`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EntityExtendBundle/Form/Guesser/ExtendFieldTypeGuesser.php#L200 "Oro\Bundle\EntityExtendBundle\Form\Guesser\ExtendFieldTypeGuesser::addConstraintsToOptions")</sup> method was removed.
* The `EnumTranslationCache::$translator`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/EntityExtendBundle/Cache/EnumTranslationCache.php#L21 "Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache::$translator")</sup> property was removed.

FilterBundle
------------
* The following methods in class `AbstractFilterExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/FilterBundle/Grid/Extension/AbstractFilterExtension.php#L223 "Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension")</sup> were removed:
   - `updateMetadata`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/FilterBundle/Grid/Extension/AbstractFilterExtension.php#L223 "Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension::updateMetadata")</sup>
   - `getFilterCacheId`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/FilterBundle/Grid/Extension/AbstractFilterExtension.php#L257 "Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension::getFilterCacheId")</sup>
* The `AbstractFilterExtension::__construct(RawConfigurationProvider $configurationProvider, FilterBagInterface $filterBag, DatagridStateProviderInterface $filtersStateProvider, FilterExecutionContext $filterExecutionContext, TranslatorInterface $translator)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/FilterBundle/Grid/Extension/AbstractFilterExtension.php#L53 "Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension")</sup> method was changed to `AbstractFilterExtension::__construct(RawConfigurationProvider $configurationProvider, FilterBagInterface $filterBag, DatagridFiltersProviderInterface $datagridFiltersProvider, FiltersMetadataProvider $filtersMetadataProvider, DatagridStateProviderInterface $filtersStateProvider, FilterExecutionContext $filterExecutionContext)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/FilterBundle/Grid/Extension/AbstractFilterExtension.php#L57 "Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension")</sup>
* The `NumberFilterType::__construct(TranslatorInterface $translator, LocaleSettings $localeSettings)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/FilterBundle/Form/Type/Filter/NumberFilterType.php#L40 "Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType")</sup> method was changed to `NumberFilterType::__construct(TranslatorInterface $translator, NumberFormatter $numberFormatter)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/FilterBundle/Form/Type/Filter/NumberFilterType.php#L34 "Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType")</sup>
* The `AbstractFilterExtension::$translator`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/FilterBundle/Grid/Extension/AbstractFilterExtension.php#L44 "Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension::$translator")</sup> property was removed.

GoogleIntegrationBundle
-----------------------
* The `UserEmailChangeListener`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/GoogleIntegrationBundle/EventListener/UserEmailChangeListener.php#L11 "Oro\Bundle\GoogleIntegrationBundle\EventListener\UserEmailChangeListener")</sup> class was removed.

ImapBundle
----------
* The `UserEmailChangeListener`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/ImapBundle/EventListener/UserEmailChangeListener.php#L12 "Oro\Bundle\ImapBundle\EventListener\UserEmailChangeListener")</sup> class was removed.

InstallerBundle
---------------
* The `ScriptManager::__construct(Kernel $kernel)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/InstallerBundle/ScriptManager.php#L35 "Oro\Bundle\InstallerBundle\ScriptManager")</sup> method was changed to `ScriptManager::__construct(KernelInterface $kernel)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/InstallerBundle/ScriptManager.php#L27 "Oro\Bundle\InstallerBundle\ScriptManager")</sup>

LayoutBundle
------------
* The `CacheImagePlaceholderProvider::getPath($filter)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LayoutBundle/Provider/Image/CacheImagePlaceholderProvider.php#L31 "Oro\Bundle\LayoutBundle\Provider\Image\CacheImagePlaceholderProvider")</sup> method was changed to `CacheImagePlaceholderProvider::getPath($filter, $referenceType)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/LayoutBundle/Provider/Image/CacheImagePlaceholderProvider.php#L26 "Oro\Bundle\LayoutBundle\Provider\Image\CacheImagePlaceholderProvider")</sup>
* The `ChainImagePlaceholderProvider::getPath($filter)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LayoutBundle/Provider/Image/ChainImagePlaceholderProvider.php#L24 "Oro\Bundle\LayoutBundle\Provider\Image\ChainImagePlaceholderProvider")</sup> method was changed to `ChainImagePlaceholderProvider::getPath($filter, $referenceType)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/LayoutBundle/Provider/Image/ChainImagePlaceholderProvider.php#L26 "Oro\Bundle\LayoutBundle\Provider\Image\ChainImagePlaceholderProvider")</sup>
* The `ConfigImagePlaceholderProvider::getPath($filter)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LayoutBundle/Provider/Image/ConfigImagePlaceholderProvider.php#L48 "Oro\Bundle\LayoutBundle\Provider\Image\ConfigImagePlaceholderProvider")</sup> method was changed to `ConfigImagePlaceholderProvider::getPath($filter, $referenceType)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/LayoutBundle/Provider/Image/ConfigImagePlaceholderProvider.php#L49 "Oro\Bundle\LayoutBundle\Provider\Image\ConfigImagePlaceholderProvider")</sup>
* The `DefaultImagePlaceholderProvider::getPath($filter)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LayoutBundle/Provider/Image/DefaultImagePlaceholderProvider.php#L31 "Oro\Bundle\LayoutBundle\Provider\Image\DefaultImagePlaceholderProvider")</sup> method was changed to `DefaultImagePlaceholderProvider::getPath($filter, $referenceType)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/LayoutBundle/Provider/Image/DefaultImagePlaceholderProvider.php#L26 "Oro\Bundle\LayoutBundle\Provider\Image\DefaultImagePlaceholderProvider")</sup>
* The `ThemeImagePlaceholderProvider::getPath($filter)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LayoutBundle/Provider/Image/ThemeImagePlaceholderProvider.php#L48 "Oro\Bundle\LayoutBundle\Provider\Image\ThemeImagePlaceholderProvider")</sup> method was changed to `ThemeImagePlaceholderProvider::getPath($filter, $referenceType)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/LayoutBundle/Provider/Image/ThemeImagePlaceholderProvider.php#L49 "Oro\Bundle\LayoutBundle\Provider\Image\ThemeImagePlaceholderProvider")</sup>
* The `ImagePlaceholderProviderInterface::getPath($filter)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LayoutBundle/Provider/Image/ImagePlaceholderProviderInterface.php#L14 "Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface")</sup> method was changed to `ImagePlaceholderProviderInterface::getPath($filter, $referenceType)`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.2/src/Oro/Bundle/LayoutBundle/Provider/Image/ImagePlaceholderProviderInterface.php#L17 "Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface")</sup>

LocaleBundle
------------
* The following methods in class `LocalizedFallbackValueAwareStrategy`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LocaleBundle/ImportExport/Strategy/LocalizedFallbackValueAwareStrategy.php#L35 "Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy")</sup> were removed:
   - `beforeProcessEntity`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LocaleBundle/ImportExport/Strategy/LocalizedFallbackValueAwareStrategy.php#L35 "Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy::beforeProcessEntity")</sup>
   - `afterProcessEntity`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LocaleBundle/ImportExport/Strategy/LocalizedFallbackValueAwareStrategy.php#L43 "Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy::afterProcessEntity")</sup>
   - `importExistingEntity`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LocaleBundle/ImportExport/Strategy/LocalizedFallbackValueAwareStrategy.php#L107 "Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy::importExistingEntity")</sup>
   - `removeNotInitializedEntities`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LocaleBundle/ImportExport/Strategy/LocalizedFallbackValueAwareStrategy.php#L187 "Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy::removeNotInitializedEntities")</sup>
   - `removedDetachedEntities`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LocaleBundle/ImportExport/Strategy/LocalizedFallbackValueAwareStrategy.php#L207 "Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy::removedDetachedEntities")</sup>
   - `setLocalizationKeys`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LocaleBundle/ImportExport/Strategy/LocalizedFallbackValueAwareStrategy.php#L225 "Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy::setLocalizationKeys")</sup>
   - `getReflectionProperty`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LocaleBundle/ImportExport/Strategy/LocalizedFallbackValueAwareStrategy.php#L244 "Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy::getReflectionProperty")</sup>
   - `findEntityByIdentityValues`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LocaleBundle/ImportExport/Strategy/LocalizedFallbackValueAwareStrategy.php#L261 "Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy::findEntityByIdentityValues")</sup>
* The `LocalizedFallbackValueAwareStrategy::$reflectionProperties`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/LocaleBundle/ImportExport/Strategy/LocalizedFallbackValueAwareStrategy.php#L21 "Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy::$reflectionProperties")</sup> property was removed.

NotificationBundle
------------------
* The `DoctrineListener::setEnabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/NotificationBundle/Provider/DoctrineListener.php#L40 "Oro\Bundle\NotificationBundle\Provider\DoctrineListener::setEnabled")</sup> method was removed.

SearchBundle
------------
* The `IndexListener::setEnabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/SearchBundle/EventListener/IndexListener.php#L81 "Oro\Bundle\SearchBundle\EventListener\IndexListener::setEnabled")</sup> method was removed.
* The `IndexListener::$enabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/SearchBundle/EventListener/IndexListener.php#L53 "Oro\Bundle\SearchBundle\EventListener\IndexListener::$enabled")</sup> property was removed.

SecurityBundle
--------------
* The following methods in class `OrganizationAccessDeniedException`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/SecurityBundle/Exception/OrganizationAccessDeniedException.php#L54 "Oro\Bundle\SecurityBundle\Exception\OrganizationAccessDeniedException")</sup> were removed:
   - `serialize`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/SecurityBundle/Exception/OrganizationAccessDeniedException.php#L54 "Oro\Bundle\SecurityBundle\Exception\OrganizationAccessDeniedException::serialize")</sup>
   - `unserialize`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/SecurityBundle/Exception/OrganizationAccessDeniedException.php#L62 "Oro\Bundle\SecurityBundle\Exception\OrganizationAccessDeniedException::unserialize")</sup>

SyncBundle
----------
* The `DoctrineTagEventListener::setEnabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/SyncBundle/EventListener/DoctrineTagEventListener.php#L71 "Oro\Bundle\SyncBundle\EventListener\DoctrineTagEventListener::setEnabled")</sup> method was removed.

TestUtils
---------
* The `ServiceLink`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Component/TestUtils/Mocks/ServiceLink.php#L7 "Oro\Component\TestUtils\Mocks\ServiceLink")</sup> class was removed.
* The `OrmTestCase::setQueryExpectationAt`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Component/TestUtils/ORM/OrmTestCase.php#L205 "Oro\Component\TestUtils\ORM\OrmTestCase::setQueryExpectationAt")</sup> method was removed.

WorkflowBundle
--------------
* The `EventTriggerCollectorListener::setEnabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/WorkflowBundle/EventListener/EventTriggerCollectorListener.php#L42 "Oro\Bundle\WorkflowBundle\EventListener\EventTriggerCollectorListener::setEnabled")</sup> method was removed.
* The `SendWorkflowStepChangesToAuditListener::setEnabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/WorkflowBundle/EventListener/SendWorkflowStepChangesToAuditListener.php#L34 "Oro\Bundle\WorkflowBundle\EventListener\SendWorkflowStepChangesToAuditListener::setEnabled")</sup> method was removed.
* The `WorkflowTransitionRecordListener::setEnabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/WorkflowBundle/EventListener/WorkflowTransitionRecordListener.php#L42 "Oro\Bundle\WorkflowBundle\EventListener\WorkflowTransitionRecordListener::setEnabled")</sup> method was removed.
* The `WorkflowTransitionRecordListener::$enabled`<sup>[[?]](https://github.com/oroinc/platform/tree/5.0.0-alpha.1/src/Oro/Bundle/WorkflowBundle/EventListener/WorkflowTransitionRecordListener.php#L27 "Oro\Bundle\WorkflowBundle\EventListener\WorkflowTransitionRecordListener::$enabled")</sup> property was removed.

