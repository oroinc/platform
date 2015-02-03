<?php

namespace Oro\Bundle\IntegrationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Status;

class ChannelRepository extends EntityRepository
{
    /**
     * Returns latest status for integration's connector and code if it exists.
     *
     * @param Integration $integration
     * @param string $connector
     * @param int|null $code
     * @return Status|null
     */
    public function getLastStatusForConnector(Integration $integration, $connector, $code = null)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('status')
            ->from('OroIntegrationBundle:Status', 'status')
            ->where('status.channel = :integration')
            ->andWhere('status.connector = :connector')
            ->setParameters(['integration' => $integration, 'connector' => $connector])
            ->orderBy('status.date', 'DESC')
            ->setFirstResult(0)
            ->setMaxResults(1);

        if ($code) {
            $queryBuilder->andWhere('status.code = :code')
                ->setParameter('code', $code);
        };

        $statuses = $queryBuilder->getQuery()->execute();

        return $statuses ? reset($statuses) : null;
    }

    /**
     * Returns channels that have configured transports
     * Assume that they are ready for sync
     *
     * @param null|string $type
     * @param boolean     $isReadOnly
     *
     * @return array
     */
    public function getConfiguredChannelsForSync($type = null, $isReadOnly = false)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.transport is NOT NULL')
            ->andWhere('c.enabled = :isEnabled')
            ->setParameter('isEnabled', true);

        if (null !== $type) {
            $qb->andWhere('c.type = :type')
                ->setParameter('type', $type);
        }

        $integrations = $qb->getQuery()->getResult();

        if ($isReadOnly) {
            $unitOfWork  = $this->getEntityManager()->getUnitOfWork();

            foreach ($integrations as $integration) {
                $unitOfWork->markReadOnly($integration);
            }
        }

        return $integrations;
    }

    /**
     * Load instance once and precache it in property
     *
     * @param int $id
     *
     * @return Integration|bool
     */
    public function getOrLoadById($id)
    {
        $unitOfWork  = $this->getEntityManager()->getUnitOfWork();
        $integration = $this->getEntityManager()->find('OroIntegrationBundle:Channel', $id);
        if ($integration === null) {
            return false;
        }

        $unitOfWork->markReadOnly($integration);

        return $integration;
    }

    /**
     * Adds status to integration, manual persist of newly created statuses
     *
     * @param Integration $integration
     * @param Status  $status
     */
    public function addStatus(Integration $integration, Status $status)
    {
        if ($this->getEntityManager()->isOpen()) {
            $integration = $this->getOrLoadById($integration->getId());

            $this->getEntityManager()->persist($status);
            $integration->addStatus($status);

            $this->getEntityManager()->flush();
        }
    }
}
