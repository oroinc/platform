- [AttachmentBundle](#attachmentbundle)
- [DashboardBundle](#dashboardbundle)
- [DataGridBundle](#datagridbundle)
- [EmailBundle](#emailbundle)
- [EntityConfigBundle](#entityconfigbundle)
- [EntityExtendBundle](#entityextendbundle)
- [ImapBundle](#imapbundle)
- [ImportExportBundle](#importexportbundle)
- [LocaleBundle](#localebundle)
- [SyncBundle](#syncbundle)
- [UserBundle](#userbundle)

AttachmentBundle
----------------
* The `AttachmentFilterAwareUrlGenerator::__construct(UrlGeneratorInterface $urlGenerator, FilterConfiguration $filterConfiguration)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/AttachmentBundle/Provider/AttachmentFilterAwareUrlGenerator.php#L34 "Oro\Bundle\AttachmentBundle\Provider\AttachmentFilterAwareUrlGenerator")</sup> method was changed to `AttachmentFilterAwareUrlGenerator::__construct(UrlGeneratorInterface $urlGenerator, AttachmentHashProvider $attachmentUrlProvider)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/AttachmentBundle/Provider/AttachmentFilterAwareUrlGenerator.php#L34 "Oro\Bundle\AttachmentBundle\Provider\AttachmentFilterAwareUrlGenerator")</sup>

DashboardBundle
---------------
* The `ConfigProvider::getWidgetConfig(string $widgetName)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/DashboardBundle/Model/ConfigProvider.php#L117 "Oro\Bundle\DashboardBundle\Model\ConfigProvider")</sup> method was changed to `ConfigProvider::getWidgetConfig(string $widgetName, bool $throwExceptionIfMissing = true)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/DashboardBundle/Model/ConfigProvider.php#L113 "Oro\Bundle\DashboardBundle\Model\ConfigProvider")</sup>
* The `WidgetConfigs::getWidgetConfig($widgetName)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/DashboardBundle/Model/WidgetConfigs.php#L173 "Oro\Bundle\DashboardBundle\Model\WidgetConfigs")</sup> method was changed to `WidgetConfigs::getWidgetConfig(string $widgetName)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/DashboardBundle/Model/WidgetConfigs.php#L171 "Oro\Bundle\DashboardBundle\Model\WidgetConfigs")</sup>

DataGridBundle
--------------
* The `Datagrid::$cachedResult`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/DataGridBundle/Datagrid/Datagrid.php#L37 "Oro\Bundle\DataGridBundle\Datagrid\Datagrid::$cachedResult")</sup> property was removed.

EmailBundle
-----------
* The `EmailTemplateTranslationType::__construct(TranslatorInterface $translator, LocalizationManager $localizationManager)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/EmailBundle/Form/Type/EmailTemplateTranslationType.php#L45 "Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationType")</sup> method was changed to `EmailTemplateTranslationType::__construct(TranslatorInterface $translator, LocalizationManager $localizationManager)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/EmailBundle/Form/Type/EmailTemplateTranslationType.php#L45 "Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationType")</sup>

EntityConfigBundle
------------------
* The `OroEntityConfigExtension::configureTestEnvironment`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/EntityConfigBundle/DependencyInjection/OroEntityConfigExtension.php#L38 "Oro\Bundle\EntityConfigBundle\DependencyInjection\OroEntityConfigExtension::configureTestEnvironment")</sup> method was removed.

EntityExtendBundle
------------------
* The `ByOriginFilter`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/EntityExtendBundle/Tools/ConfigFilter/ByOriginFilter.php#L8 "Oro\Bundle\EntityExtendBundle\Tools\ConfigFilter\ByOriginFilter")</sup> class was removed.
* The `ExtendExtension::createEnum(Schema $schema, $enumCode, $isMultiple = false, $isPublic = false, $immutable = false, array $options = [])`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/EntityExtendBundle/Migration/Extension/ExtendExtension.php#L159 "Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension")</sup> method was changed to `ExtendExtension::createEnum(Schema $schema, $enumCode, $isMultiple = false, $isPublic = false, $immutable = false, array $options = [], array $identityFields)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/EntityExtendBundle/Migration/Extension/ExtendExtension.php#L164 "Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension")</sup>
* The `UpdateCommand::__construct(EntityExtendUpdateProcessor $entityExtendUpdateProcessor)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/EntityExtendBundle/Command/UpdateCommand.php#L24 "Oro\Bundle\EntityExtendBundle\Command\UpdateCommand")</sup> method was changed to `UpdateCommand::__construct(EntityExtendUpdateProcessor $entityExtendUpdateProcessor, ConfigManager $configManager)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/EntityExtendBundle/Command/UpdateCommand.php#L34 "Oro\Bundle\EntityExtendBundle\Command\UpdateCommand")</sup>

ImapBundle
----------
* The `Decode::splitMessage($message, &$headers, &$body, $EOL, $strict = false)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Mime/Decode.php#L23 "Oro\Bundle\ImapBundle\Mime\Decode")</sup> method was changed to `Decode::splitMessage($message, &$headers, &$body, $EOL, $strict = false)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mime/Decode.php#L41 "Oro\Bundle\ImapBundle\Mime\Decode")</sup>
* The `ImapEmailGoogleOauth2Manager::__construct(HttpMethodsClient $httpClient, ResourceOwnerMap $resourceOwnerMap, ConfigManager $configManager, ManagerRegistry $doctrine)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Manager/ImapEmailGoogleOauth2Manager.php#L44 "Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager")</sup> method was changed to `ImapEmailGoogleOauth2Manager::__construct(HttpMethodsClientInterface $httpClient, ResourceOwnerMap $resourceOwnerMap, ConfigManager $configManager, ManagerRegistry $doctrine)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Manager/ImapEmailGoogleOauth2Manager.php#L44 "Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager")</sup>
* The `ImapEmailManager::getAcceptLanguage(Headers $headers)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Manager/ImapEmailManager.php#L256 "Oro\Bundle\ImapBundle\Manager\ImapEmailManager")</sup> method was changed to `ImapEmailManager::getAcceptLanguage(Headers $headers)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Manager/ImapEmailManager.php#L261 "Oro\Bundle\ImapBundle\Manager\ImapEmailManager")</sup>
* The `Headers::addHeader(HeaderInterface $header)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Mail/Headers.php#L86 "Oro\Bundle\ImapBundle\Mail\Headers")</sup> method was changed to `Headers::addHeader(HeaderInterface $header)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Headers.php#L107 "Oro\Bundle\ImapBundle\Mail\Headers")</sup>
* The `Attachment::__construct(Part $part)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Mail/Storage/Attachment.php#L22 "Oro\Bundle\ImapBundle\Mail\Storage\Attachment")</sup> method was changed to `Attachment::__construct(Part $part)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Storage/Attachment.php#L23 "Oro\Bundle\ImapBundle\Mail\Storage\Attachment")</sup>
* The `Body::__construct(Part $part)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Mail/Storage/Body.php#L23 "Oro\Bundle\ImapBundle\Mail\Storage\Body")</sup> method was changed to `Body::__construct(Part $part)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Storage/Body.php#L26 "Oro\Bundle\ImapBundle\Mail\Storage\Body")</sup>
* The following methods in class `Message`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Storage/Message.php#L150 "Oro\Bundle\ImapBundle\Mail\Storage\Message")</sup> were changed:
  > - `getPartContentDisposition($part, $format)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Mail/Storage/Message.php#L150 "Oro\Bundle\ImapBundle\Mail\Storage\Message")</sup>
  > - `getPartContentDisposition($part, $format)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Storage/Message.php#L150 "Oro\Bundle\ImapBundle\Mail\Storage\Message")</sup>

  > - `getMultiPartPriorContentType(Part $multiPart)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Mail/Storage/Message.php#L161 "Oro\Bundle\ImapBundle\Mail\Storage\Message")</sup>
  > - `getMultiPartPriorContentType(Part $multiPart)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Storage/Message.php#L161 "Oro\Bundle\ImapBundle\Mail\Storage\Message")</sup>

* The following methods in class `ContentProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Processor/ContentProcessor.php#L21 "Oro\Bundle\ImapBundle\Mail\Processor\ContentProcessor")</sup> were changed:
  > - `processText(PartInterface $part)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Mail/Processor/ContentProcessor.php#L20 "Oro\Bundle\ImapBundle\Mail\Processor\ContentProcessor")</sup>
  > - `processText(PartInterface $part)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Processor/ContentProcessor.php#L21 "Oro\Bundle\ImapBundle\Mail\Processor\ContentProcessor")</sup>

  > - `getMultipartContentRecursively(PartInterface $multipart, $format)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Mail/Processor/ContentProcessor.php#L51 "Oro\Bundle\ImapBundle\Mail\Processor\ContentProcessor")</sup>
  > - `getMultipartContentRecursively(PartInterface $multipart, $format)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Processor/ContentProcessor.php#L52 "Oro\Bundle\ImapBundle\Mail\Processor\ContentProcessor")</sup>

* The following methods in class `ImageExtractor`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Processor/ImageExtractor.php#L17 "Oro\Bundle\ImapBundle\Mail\Processor\ImageExtractor")</sup> were changed:
  > - `extract(PartInterface $multipart)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Mail/Processor/ImageExtractor.php#L14 "Oro\Bundle\ImapBundle\Mail\Processor\ImageExtractor")</sup>
  > - `extract(PartInterface $multipart)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Processor/ImageExtractor.php#L17 "Oro\Bundle\ImapBundle\Mail\Processor\ImageExtractor")</sup>

  > - `supports(PartInterface $part)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Mail/Processor/ImageExtractor.php#L40 "Oro\Bundle\ImapBundle\Mail\Processor\ImageExtractor")</sup>
  > - `supports(PartInterface $part)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Processor/ImageExtractor.php#L43 "Oro\Bundle\ImapBundle\Mail\Processor\ImageExtractor")</sup>

* The `ContentIdExtractorInterface::extract(PartInterface $multipart)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImapBundle/Mail/Processor/ContentIdExtractorInterface.php#L14 "Oro\Bundle\ImapBundle\Mail\Processor\ContentIdExtractorInterface")</sup> method was changed to `ContentIdExtractorInterface::extract(PartInterface $multipart)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/ImapBundle/Mail/Processor/ContentIdExtractorInterface.php#L17 "Oro\Bundle\ImapBundle\Mail\Processor\ContentIdExtractorInterface")</sup>

ImportExportBundle
------------------
* The following classes were removed:
   - `IdentityValidationLoader`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImportExportBundle/Validator/IdentityValidationLoader.php#L16 "Oro\Bundle\ImportExportBundle\Validator\IdentityValidationLoader")</sup>
   - `IdentityValidationLoaderPass`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/ImportExportBundle/DependencyInjection/Compiler/IdentityValidationLoaderPass.php#L12 "Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\IdentityValidationLoaderPass")</sup>

LocaleBundle
------------
* The following methods in class `NumberFormatter`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/LocaleBundle/Formatter/NumberFormatter.php#L427 "Oro\Bundle\LocaleBundle\Formatter\NumberFormatter")</sup> were removed:
   - `adjustFormatter`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/LocaleBundle/Formatter/NumberFormatter.php#L427 "Oro\Bundle\LocaleBundle\Formatter\NumberFormatter::adjustFormatter")</sup>
   - `parseAttributes`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/LocaleBundle/Formatter/NumberFormatter.php#L458 "Oro\Bundle\LocaleBundle\Formatter\NumberFormatter::parseAttributes")</sup>
   - `parseConstantValue`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/LocaleBundle/Formatter/NumberFormatter.php#L474 "Oro\Bundle\LocaleBundle\Formatter\NumberFormatter::parseConstantValue")</sup>
   - `parseStyle`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/LocaleBundle/Formatter/NumberFormatter.php#L495 "Oro\Bundle\LocaleBundle\Formatter\NumberFormatter::parseStyle")</sup>
* The `NumberFormatter::__construct(LocaleSettings $localeSettings)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/LocaleBundle/Formatter/NumberFormatter.php#L34 "Oro\Bundle\LocaleBundle\Formatter\NumberFormatter")</sup> method was changed to `NumberFormatter::__construct(LocaleSettings $localeSettings, IntlNumberFormatterFactory $intlNumberFormatterFactory)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/LocaleBundle/Formatter/NumberFormatter.php#L39 "Oro\Bundle\LocaleBundle\Formatter\NumberFormatter")</sup>

SyncBundle
----------
* The following classes were removed:
   - `PubSubRouterCache`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/SyncBundle/Cache/PubSubRouterCache.php#L10 "Oro\Bundle\SyncBundle\Cache\PubSubRouterCache")</sup>
   - `OriginRegistry`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/SyncBundle/Authentication/Origin/OriginRegistry.php#L10 "Oro\Bundle\SyncBundle\Authentication\Origin\OriginRegistry")</sup>
* The `ClientEventListener::__construct(ClientEventListener $decoratedClientEventListener, WebsocketAuthenticationProviderInterface $websocketAuthenticationProvider, ClientStorageInterface $clientStorage)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/SyncBundle/EventListener/ClientEventListener.php#L41 "Oro\Bundle\SyncBundle\EventListener\ClientEventListener")</sup> method was changed to `ClientEventListener::__construct(ClientEventListener $decoratedClientEventListener, WebsocketAuthenticationProviderInterface $websocketAuthenticationProvider, ClientStorageInterface $clientStorage)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/SyncBundle/EventListener/ClientEventListener.php#L42 "Oro\Bundle\SyncBundle\EventListener\ClientEventListener")</sup>
* The following methods in class `ClientManipulator`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/SyncBundle/Client/ClientManipulator.php#L61 "Oro\Bundle\SyncBundle\Client\ClientManipulator")</sup> were changed:
  > - `__construct(ClientManipulatorInterface $decoratedClientManipulator, ClientStorageInterface $clientStorage, UserProvider $userProvider)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/SyncBundle/Client/ClientManipulator.php#L45 "Oro\Bundle\SyncBundle\Client\ClientManipulator")</sup>
  > - `__construct(ClientManipulatorInterface $decoratedClientManipulator, ClientStorageInterface $clientStorage, UserProvider $userProvider, TicketProviderInterface $ticketProvider, WebsocketAuthenticationProviderInterface $websocketAuthenticationProvider)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/SyncBundle/Client/ClientManipulator.php#L61 "Oro\Bundle\SyncBundle\Client\ClientManipulator")</sup>

  > - `getAll(Topic $topic, $anonymous = false)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/SyncBundle/Client/ClientManipulator.php#L104 "Oro\Bundle\SyncBundle\Client\ClientManipulator")</sup>
  > - `getAll(Topic $topic, bool $anonymous = false)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/SyncBundle/Client/ClientManipulator.php#L115 "Oro\Bundle\SyncBundle\Client\ClientManipulator")</sup>

* The `WampClient::connect($target)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/SyncBundle/Client/Wamp/WampClient.php#L48 "Oro\Bundle\SyncBundle\Client\Wamp\WampClient")</sup> method was changed to `WampClient::connect(string $target)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/SyncBundle/Client/Wamp/WampClient.php#L119 "Oro\Bundle\SyncBundle\Client\Wamp\WampClient")</sup>
* The `ClientManipulator::findByUsername`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/SyncBundle/Client/ClientManipulator.php#L96 "Oro\Bundle\SyncBundle\Client\ClientManipulator::findByUsername")</sup> method was removed.

UserBundle
----------
* The `UserChecker::__construct(TokenStorageInterface $tokenStorage, FlashBagInterface $flashBag, TranslatorInterface $translator)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/UserBundle/Security/UserChecker.php#L36 "Oro\Bundle\UserBundle\Security\UserChecker")</sup> method was changed to `UserChecker::__construct(TokenStorageInterface $tokenStorage)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-beta/src/Oro/Bundle/UserBundle/Security/UserChecker.php#L26 "Oro\Bundle\UserBundle\Security\UserChecker")</sup>
* The following properties in class `UserChecker`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/UserBundle/Security/UserChecker.php#L26 "Oro\Bundle\UserBundle\Security\UserChecker")</sup> were removed:
   - `$flashBag`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/UserBundle/Security/UserChecker.php#L26 "Oro\Bundle\UserBundle\Security\UserChecker::$flashBag")</sup>
   - `$translator`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha.3/src/Oro/Bundle/UserBundle/Security/UserChecker.php#L29 "Oro\Bundle\UserBundle\Security\UserChecker::$translator")</sup>

