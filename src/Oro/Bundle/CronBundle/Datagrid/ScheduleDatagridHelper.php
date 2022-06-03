<?php

namespace Oro\Bundle\CronBundle\Datagrid;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Provides a method to get enabled schedules to be displayed in a schedules datagrid.
 */
class ScheduleDatagridHelper
{
    private ManagerRegistry $doctrine;
    private FeatureChecker $featureChecker;

    public function __construct(ManagerRegistry $doctrine, FeatureChecker $featureChecker)
    {
        $this->doctrine = $doctrine;
        $this->featureChecker = $featureChecker;
    }

    public function getEnabledSchedulesQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->doctrine->getManagerForClass(Schedule::class)->createQueryBuilder();
        $queryBuilder
            ->select('s.command, s.arguments, s.definition')
            ->from(Schedule::class, 's');

        $disabledJobs = $this->featureChecker->getDisabledResourcesByType('cron_jobs');
        if ($disabledJobs) {
            $queryBuilder
                ->where($queryBuilder->expr()->notIn('s.command', ':disabledJobs'))
                ->setParameter('disabledJobs', $disabledJobs);
        }

        return $queryBuilder;
    }
}
