<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\ChannelDeleteProviderInterface;

class ChannelRelatedDataDeleteProvider implements ChannelDeleteProviderInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * {@inheritdoc}
     */
    public function isSupport($channelType)
    {
        return true;
    }

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRelatedData(Channel $channel)
    {
        // delete embedded forms
        $this->em->createQuery(
            'DELETE FROM OroEmbeddedFormBundle:EmbeddedForm e WHERE e.channel = ' . $channel->getId()
        )->execute();
    }
}
