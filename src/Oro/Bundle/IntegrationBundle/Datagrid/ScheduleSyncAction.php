<?php

namespace Oro\Bundle\IntegrationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AjaxAction;

class ScheduleSyncAction extends AjaxAction
{
    /**
     * @return array
     */
    public function getOptions()
    {
        $options = parent::getOptions();
        $options['frontend_type'] = 'schedule-sync';

        return $options;
    }
}
