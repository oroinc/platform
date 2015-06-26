<?php

namespace Oro\Bundle\CalendarBundle\EventListener\Datagrid;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class ActivityGridListener
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var LocaleSettings */
    protected $localeSettings;

    /**
     * @param ActivityManager     $activityManager
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param LocaleSettings      $localeSettings
     */
    public function __construct(
        ActivityManager $activityManager,
        EntityRoutingHelper $entityRoutingHelper,
        LocaleSettings $localeSettings
    ) {
        $this->activityManager     = $activityManager;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->localeSettings      = $localeSettings;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $parameters = $datagrid->getParameters();
            $entityClass = $this->entityRoutingHelper->resolveEntityClass($parameters->get('entityClass'));
            $entityId = $parameters->get('entityId');

            $qb = $datasource->getQueryBuilder();

            // apply activity filter
            $this->activityManager->addFilterByTargetEntity($qb, $entityClass, $entityId);

            // apply filter by date
            $start = new \DateTime('now', new \DateTimeZone($this->localeSettings->getTimeZone()));
            $start->setTime(0, 0, 0);
            $qb->andWhere('event.start >= :date OR event.end >= :date')
                ->setParameter('date', $start);
        }
    }
}
