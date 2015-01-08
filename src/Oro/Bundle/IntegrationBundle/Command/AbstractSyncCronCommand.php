<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use JMS\JobQueueBundle\Entity\Job;

use Doctrine\ORM\QueryBuilder;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;

abstract class AbstractSyncCronCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const SYNC_PROCESSOR = 'oro_integration.sync.processor';

    /**
     * Check is job running (from previous schedule)
     *
     * @param null|int $integrationId
     *
     * @return bool
     */
    protected function isJobRunning($integrationId)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getService('doctrine.orm.entity_manager')
            ->getRepository('JMSJobQueueBundle:Job')
            ->createQueryBuilder('j')
            ->select('count(j.id)')
            ->andWhere('j.command=:commandName')
            ->andWhere('j.state=:stateName')
            ->setParameter('commandName', $this->getName())
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

        $running = $qb->getQuery()
            ->getSingleScalarResult();

        return $running > 1;
    }

    /**
     * Get service from DI container by id
     *
     * @param string $id
     *
     * @return object
     */
    protected function getService($id)
    {
        return $this->getContainer()->get($id);
    }
}
