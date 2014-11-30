<?php

namespace Oro\Bundle\CalendarBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfigHelper;

class SystemCalendarGridListener
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var SystemCalendarConfigHelper */
    protected $calendarConfigHelper;

    /**
     * @param SecurityFacade             $securityFacade
     * @param SystemCalendarConfigHelper $calendarConfigHelper
     */
    public function __construct(SecurityFacade $securityFacade, SystemCalendarConfigHelper $calendarConfigHelper)
    {
        $this->securityFacade       = $securityFacade;
        $this->calendarConfigHelper = $calendarConfigHelper;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        // show 'public' column only if both public and system calendars are enabled
        if (!$this->calendarConfigHelper->isPublicCalendarSupported()
            || !$this->calendarConfigHelper->isSystemCalendarSupported()
        ) {
            $config = $event->getConfig();
            $this->removeColumn($config, 'public');
        }
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            // @todo: add ACL check for public calendars here
            $isPublicGranted = $this->calendarConfigHelper->isPublicCalendarSupported();
            $isSystemGranted = $this->calendarConfigHelper->isSystemCalendarSupported()
                && $this->securityFacade->isGranted('oro_system_calendar_view');
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


    /**
     * @param DatagridConfiguration $config
     * @param string                $fieldName
     */
    protected function removeColumn(DatagridConfiguration $config, $fieldName)
    {
        $config->offsetUnsetByPath(sprintf('[columns][%s]', $fieldName));
        $config->offsetUnsetByPath(sprintf('[filters][columns][%s]', $fieldName));
        $config->offsetUnsetByPath(sprintf('[sorters][columns][%s]', $fieldName));
    }
}
