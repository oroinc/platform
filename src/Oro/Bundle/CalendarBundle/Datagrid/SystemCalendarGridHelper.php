<?php

namespace Oro\Bundle\CalendarBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\Grid\GridHelper as BaseGridHelper;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;

class SystemCalendarGridHelper extends BaseGridHelper
{
    /**
     * Constructor
     *
     * @param EntityProvider      $entityProvider
     */
    public function __construct(EntityProvider $entityProvider)
    {
        parent::__construct($entityProvider);
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
                return [
                    'update' => false,
                    'delete' => false,
                ];
            }
        };
    }
}
