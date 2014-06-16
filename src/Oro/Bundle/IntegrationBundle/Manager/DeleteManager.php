<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class DeleteManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ChannelDeleteProviderInterface[]
     */
    protected $deleteProviders;

    /**
     * @param EntityManager   $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Add delete channel provider
     *
     * @param ChannelDeleteProviderInterface $deleteProvider
     */
    public function addProvider(ChannelDeleteProviderInterface $deleteProvider)
    {
        $this->deleteProviders[] = $deleteProvider;
    }

    /**
     * Delete channel
     *
     * @param Channel $channel
     * @return bool
     */
    public function deleteChannel(Channel $channel)
    {
        try {
            $this->em->getConnection()->beginTransaction();
            $channelType = $channel->getType();
            foreach ($this->deleteProviders as $deleteProvider) {
                if ($deleteProvider->isSupport($channelType)) {
                    $deleteProvider->deleteRelatedData($channel);
                }
            }
            $this->em->remove($channel);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            return false;
        }

        return true;
    }
}
