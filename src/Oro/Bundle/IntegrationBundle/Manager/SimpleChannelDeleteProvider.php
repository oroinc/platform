<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class SimpleChannelDeleteProvider implements ChannelDeleteProviderInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedChannelType()
    {
        return 'simple';
    }

    /**
     * {@inheritdoc}
     */
    public function processDelete(Channel $channel)
    {
        try {
            $this->em->remove($channel);
            $this->em->flush();
        } catch (\Exception $error) {
            return false;
        }

        return true;
    }
}
