<?php

namespace Oro\Bundle\IntegrationBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Status;

/**
 * Doctrine repository for Channel entity
 */
class ChannelRepository extends EntityRepository
{
    const BUFFER_SIZE = 100;

    /**
     * Returns latest status for integration's connector and code if it exists.
     *
     * @param Integration $integration
     * @param string      $connector
     * @param int|null    $code
     *
     * @return Status|null
     */
    public function getLastStatusForConnector(Integration $integration, $connector, $code = null)
    {
        $queryBuilder = $this->getConnectorStatusesQueryBuilder($integration, $connector, $code);
        $queryBuilder
            ->setFirstResult(0)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Integration $integration
     * @param string      $connector
     * @param int|null    $code
     *
     * @return Status[]|\Iterator
     */
    public function getConnectorStatuses(Integration $integration, $connector, $code = null)
    {
        $iterator = new BufferedIdentityQueryResultIterator(
            $this->getConnectorStatusesQueryBuilder($integration, $connector, $code)
        );
        $iterator->setBufferSize(self::BUFFER_SIZE);

        return $iterator;
    }

    /**
     * @param Integration $integration
     * @param string      $connector
     * @param int|null    $code
     *
     * @return QueryBuilder
     */
    public function getConnectorStatusesQueryBuilder(Integration $integration, $connector, $code = null)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('status')
            ->from('OroIntegrationBundle:Status', 'status')
            ->where('status.channel = :integration')
            ->andWhere('status.connector = :connector')
            ->setParameters(['integration' => $integration, 'connector' => (string)$connector])
            ->orderBy('status.date', Criteria::DESC);

        if ($code) {
            $queryBuilder->andWhere('status.code = :code')
                ->setParameter('code', (string)$code);
        }

        return $queryBuilder;
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
            ->setParameter('isEnabled', true, Types::BOOLEAN);

        if (null !== $type) {
            $qb->andWhere('c.type = :type')
                ->setParameter('type', $type, Types::STRING);
        } else {
            $qb->andWhere($qb->expr()->neq('c.connectors', ':emptyConnectors'))
                ->setParameter('emptyConnectors', [], Types::ARRAY);
        }

        $integrations = $qb->getQuery()->getResult();

        if ($isReadOnly) {
            $unitOfWork = $this->getEntityManager()->getUnitOfWork();

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
     * Adds status to integration, manual persist of newly created statuses and do flush.
     */
    public function addStatusAndFlush(Integration $integration, Status $status)
    {
        if ($this->getEntityManager()->isOpen()) {
            $integration = $this->getOrLoadById($integration->getId());

            $this->getEntityManager()->persist($status);
            $integration->addStatus($status);

            $this->getEntityManager()->flush();
        }
    }

    public function countActiveIntegrations(?string $type = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.enabled = :enabled')
            ->setParameter('enabled', true);

        if ($type) {
            $qb
                ->andWhere('c.type = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
