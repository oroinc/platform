UPGRADE FROM 1.6 to 1.7
=======================

####OroIntegrationBundle:
- `Oro\Bundle\IntegrationBundle\Entity\Channel::getStatusesForConnector` method marked as deprecated in favor of new `Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository::getLastStatusForConnector` method because of performance impact.
