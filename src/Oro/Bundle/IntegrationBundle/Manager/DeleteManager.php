<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

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
     * Add delete integration provider
     *
     * @param DeleteProviderInterface $deleteProvider
     */
    public function addProvider(DeleteProviderInterface $deleteProvider)
    {
        $this->deleteProviders[] = $deleteProvider;
    }

    /**
     * Delete integration
     *
     * @param integration $integration
     *
     * @return bool
     */
    public function delete(Integration $integration)
    {
        try {
            $this->em->getConnection()->beginTransaction();
            $type = $integration->getType();
            foreach ($this->deleteProviders as $deleteProvider) {
                if ($deleteProvider->supports($type)) {
                    $deleteProvider->deleteRelatedData($integration);
                }
            }
            $this->em->remove($integration);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            return false;
        }

        return true;
    }
}
