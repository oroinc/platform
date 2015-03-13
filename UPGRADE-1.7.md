UPGRADE FROM 1.6 to 1.7
=======================

####OroIntegrationBundle:
- `Oro\Bundle\IntegrationBundle\Entity\Channel::getStatusesForConnector` method marked as deprecated in favor of new `Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository::getLastStatusForConnector` method because of performance impact.
- `Oro\Bundle\IntegrationBundle\Command\AbstractSyncCronCommand::SYNC_PROCESSOR` constant removed
- `Oro\Bundle\IntegrationBundle\Provider\SyncProcessor::processIntegrationConnector` - removed last parameter $saveStatus
- `Oro\Bundle\IntegrationBundle\Provider\SyncProcessor::processImport` - removed last parameter $saveStatus
- `Oro\Bundle\IntegrationBundle\Event\SyncEvent` - second constructor argument `$configuration` now must be of type `array`
- `Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator` - added public method `getInitializedTransport(Integration $integration, $markReadOnly = false)`

####OroDistributionBundle:
-  Error handler for Errors and Recoverable Fatal Errors added `Oro\Bundle\DistributionBundle\Error\ErrorHandler::handleError`.
   It will throw \ErrorException for all such errors and now there is a possibility to catch it using try-catch construction.
-  `Oro\Bundle\DistributionBundle\Error\ErrorHandler::handleWarning` - marked as deprecated and will be changed to protected in 1.9
