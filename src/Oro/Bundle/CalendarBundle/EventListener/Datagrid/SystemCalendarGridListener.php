<?php

namespace Oro\Bundle\CalendarBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

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
            $isPublicGranted = true; // @todo: add ACL check for public calendars here
            $isSystemGranted = $this->securityFacade->isGranted('oro_system_calendar_view');
            if ($isPublicGranted && $isSystemGranted) {
                $datasource->getQueryBuilder()
                    ->andWhere('(sc.public = :public OR sc.organization = :organizationId)')
                    ->setParameter('public', true)
                    ->setParameter('organizationId', $this->securityFacade->getOrganizationId());
            } elseif ($isPublicGranted) {
                $datasource->getQueryBuilder()
                    ->andWhere('sc.public = :public')
                    ->setParameter('public', true);
            } elseif ($isSystemGranted) {
                $datasource->getQueryBuilder()
                    ->andWhere('sc.organization = :organizationId')
                    ->setParameter('organizationId', $this->securityFacade->getOrganizationId());
            } else {
                // it is denied to view both public and system calendars
                $datasource->getQueryBuilder()
                    ->andWhere('1 = 0');
            }
        }
    }

    /**
     * Returns callback for configuration of grid/actions visibility per row
     *
     * @return callable
     */
    public function getActionConfigurationClosure()
    {
        return function (ResultRecordInterface $record) {
            if ($record->getValue('public')) {
                // @todo: add ACL check for public calendars here
                return [
                    'update' => false,
                    'delete' => false,
                ];
            }

            $result = [];
            if (!$this->securityFacade->isGranted('oro_system_calendar_update')) {
                $result['update'] = false;
            }
            if (!$this->securityFacade->isGranted('oro_system_calendar_delete')) {
                $result['delete'] = false;
            }

            return $result;
        };
    }
}
