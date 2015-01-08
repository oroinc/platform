<?php

namespace Oro\Bundle\CalendarBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class SystemCalendarEventGridHelper
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
     * Returns callback for configuration of grid/actions visibility per row
     *
     * @return callable
     */
    public function getPublicActionConfigurationClosure()
    {
        return function (ResultRecordInterface $record) {
            if ($this->securityFacade->isGranted('oro_public_calendar_event_management')) {
                return [];
            } else {
                return [
                    'update' => false,
                    'delete' => false,
                ];
            }
        };
    }

    /**
     * Returns callback for configuration of grid/actions visibility per row
     *
     * @return callable
     */
    public function getSystemActionConfigurationClosure()
    {
        return function (ResultRecordInterface $record) {
            if ($this->securityFacade->isGranted('oro_system_calendar_event_management')) {
                return [];
            } else {
                return [
                    'update' => false,
                    'delete' => false,
                ];
            }
        };
    }
}
