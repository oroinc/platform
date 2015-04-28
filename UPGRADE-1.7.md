UPGRADE FROM 1.6 to 1.7
=======================

####OroEmbeddedFormBundle:
- The `Oro\Bundle\EmbeddedFormBundle\Form\Type\CustomLayoutFormInterface` interface and `Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager::getCustomFormLayoutByFormType` method are marked deprecated in favor of using new layout update mechanism introduced by the **OroLayoutBundle**.

####OroIntegrationBundle:
- `Oro\Bundle\IntegrationBundle\Entity\Channel::getStatusesForConnector` method marked as deprecated in favor of new `Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository::getLastStatusForConnector` method because of performance impact.
- `Oro\Bundle\IntegrationBundle\Command\AbstractSyncCronCommand::SYNC_PROCESSOR` constant removed
- `Oro\Bundle\IntegrationBundle\Provider\SyncProcessor::processIntegrationConnector` - removed last parameter $saveStatus
- `Oro\Bundle\IntegrationBundle\Provider\SyncProcessor::processImport` - removed last parameter $saveStatus
- `Oro\Bundle\IntegrationBundle\Event\SyncEvent` - second constructor argument `$configuration` now must be of type `array`
- `Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator` - added public method `getInitializedTransport(Integration $integration, $markReadOnly = false)`
- Integration import processors may be tagged with `oro_integration.sync_processor` DIC tag with `integration` name. During integration import appropriate processor will be used if registered.

####OroDistributionBundle:
-  Error handler for Errors and Recoverable Fatal Errors added `Oro\Bundle\DistributionBundle\Error\ErrorHandler::handleError`.
   It will throw \ErrorException for all such errors and now there is a possibility to catch it using try-catch construction.
-  `Oro\Bundle\DistributionBundle\Error\ErrorHandler::handleWarning` - marked as deprecated and will be changed to protected in 1.9
  
####Composer dependencies:
- Removed abandoned package `guzzle/http` in favor of `guzzle/guzzle`.

####OroEntityExtendBundle:
- Added parameter `DoctrineHelper $doctrineHelper` to constructor of `Oro\Bundle\EntityExtendBundle\Form\Extension\DynamicFieldsExtension` class

####ImportExportBundle
 - `Oro\Bundle\ImportExportBundle\Context\ContextInterface` added $incrementBy integer parameter for methods: incrementReadCount, incrementAddCount, incrementUpdateCount, incrementReplaceCount, incrementDeleteCount, incrementErrorEntriesCount
