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
     * @var DeleteProviderInterface[]
     */
    protected $deleteProviders;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Add delete channel provider
     *
     * @param DeleteProviderInterface $deleteProvider
     */
    public function addProvider(DeleteProviderInterface $deleteProvider)
    {
        $this->deleteProviders[] = $deleteProvider;
    }

    /**
     * Delete channel
     *
     * @param Channel $channel
     *
     * @return bool
     */
    public function delete(Channel $channel)
    {
        try {
            $this->em->getConnection()->beginTransaction();
            $type = $channel->getType();
            foreach ($this->deleteProviders as $deleteProvider) {
                if ($deleteProvider->supports($type)) {
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
