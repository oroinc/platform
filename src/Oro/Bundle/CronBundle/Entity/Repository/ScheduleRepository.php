<?php

namespace Oro\Bundle\CronBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class ScheduleRepository extends EntityRepository
{
    /** @var FeatureChecker */
    private $featureChecker;

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getEnabledSchedulesQb()
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('s.command, s.arguments, s.definition')
            ->from('OroCronBundle:Schedule', 's')
        ;

        $disabledJobs = $this->featureChecker->getDisabledResourcesByType('cron_jobs');
        if ($disabledJobs) {
            $queryBuilder
                ->where($queryBuilder->expr()->notIn('s.command', ':disabledJobs'))
                ->setParameter('disabledJobs', $disabledJobs)
            ;
        }

        return $queryBuilder;
    }

    /**
     * @param FeatureChecker $checker
     */
    public function setFeatureChecker(FeatureChecker $checker)
    {
        $this->featureChecker = $checker;
    }
}
