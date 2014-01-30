<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;

class ConnectorContextMediator
{
    /** @var TypesRegistry */
    protected $registry;

    /** @var ChannelRepository */
    protected $channelRepository;

    public function __construct(ServiceLink $registryLink, EntityManager $em)
    {
        $this->registryLink      = $registryLink;
        $this->channelRepository = $em->getRepository('OroIntegrationBundle:Channel');
    }

    /**
     * Get prepared transport
     *
     * @param ContextInterface $context
     *
     * @return TransportInterface
     */
    public function getTransport(ContextInterface $context)
    {
        $channel = $this->getChannel($context);
        return clone $this->registryLink->getService()
            ->getTransportTypeBySettingEntity($channel->getTransport(), $channel->getType());
    }

    /**
     * Get channel instance
     *
     * @param ContextInterface $context
     *
     * @return Channel
     */
    public function getChannel(ContextInterface $context)
    {
        return $this->channelRepository->getOrLoadById($context->getOption('channel'));
    }
}
