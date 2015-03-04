UPGRADE FROM 1.6 to 1.7
=======================

####OroIntegrationBundle:
- `Oro\Bundle\IntegrationBundle\Entity\Channel::getStatusesForConnector` method marked as deprecated in favor of new `Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository::getLastStatusForConnector` method because of performance impact.

####OroEmbeddedFormBundle:
- The `Oro\Bundle\EmbeddedFormBundle\Form\Type\CustomLayoutFormInterface` interface and `Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager::getCustomFormLayoutByFormType` method are marked deprecated in favor of using new layout update mechanism introduced by Oro LayoutBundle.
