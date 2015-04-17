<?php

namespace Oro\Bundle\IntegrationBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Status;

class ChannelRepository extends EntityRepository
{
    const BUFFER_SIZE = 100;

    /**
     * @param string $commandName
     * @param int|null $integrationId
     * @return int
     */
    public function getRunningSyncJobsCount($commandName, $integrationId = null)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getEntityManager()
            ->getRepository('JMSJobQueueBundle:Job')
            ->createQueryBuilder('j')
            ->select('count(j.id)')
            ->andWhere('j.command=:commandName')
            ->andWhere('j.state=:stateName')
            ->setParameter('commandName', $commandName)
            ->setParameter('stateName', Job::STATE_RUNNING);

        if ($integrationId) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('j.args', ':integrationIdType1'),
                    $qb->expr()->like('j.args', ':integrationIdType2'),
                    $qb->expr()->andX(
                        $qb->expr()->notLike('j.args', ':noIntegrationIdType1'),
                        $qb->expr()->notLike('j.args', ':noIntegrationIdType2')
                    )
                )
            )
                ->setParameter('integrationIdType1', '%--integration-id=' . $integrationId . '%')
                ->setParameter('noIntegrationIdType1', '%--integration-id=%')
                ->setParameter('integrationIdType2', '%-i=' . $integrationId . '%')
                ->setParameter('noIntegrationIdType2', '%-i=%');
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

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
        $queryBuilder = $this->getConnectorStatusesQueryBuilder($integration, $connector, $code);
        $queryBuilder
            ->setFirstResult(0)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Integration $integration
     * @param string $connector
     * @param int|null $code
     * @return Status[]|\Iterator
     */
    public function getConnectorStatuses(Integration $integration, $connector, $code = null)
    {
        $iterator = new BufferedQueryResultIterator(
            $this->getConnectorStatusesQueryBuilder($integration, $connector, $code)
        );
        $iterator->setBufferSize(self::BUFFER_SIZE);

        return $iterator;
    }

    /**
     * @param Integration $integration
     * @param string $connector
     * @param int|null $code
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
        };

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
