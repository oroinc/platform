<?php

namespace Oro\Bundle\CronBundle\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;

class JmsJobHelper
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $state
     *
     * @return mixed
     */
    public function getPendingJobsCount($state)
    {
        return $this->registry->getRepository('JMSJobQueueBundle:Job')
            ->createQueryBuilder('job')
            ->select('COUNT(job.id)')
            ->where('job.state = :state_param')
            ->setParameter('state_param', $state)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
