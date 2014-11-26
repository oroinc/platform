<?php

namespace Oro\Bundle\CalendarBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;

class SystemCalendarGridListener
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $qb = $datasource->getQueryBuilder();
            $qb
                ->andWhere('sc.organization = :organizationId')
                ->setParameter('organizationId', $this->securityFacade->getOrganizationId());
            if (!$this->securityFacade->isGranted('oro_system_calendar_view')) {
                $qb
                    ->andWhere('sc.public = :public')
                    ->setParameter('public', true);
            }
        }
    }
}
