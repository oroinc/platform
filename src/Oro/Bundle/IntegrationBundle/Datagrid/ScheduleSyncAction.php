<?php

namespace Oro\Bundle\IntegrationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AjaxAction;

/**
 * Provides a custom datagrid action for scheduling synchronization of integration channels.
 *
 * This action extends the standard AJAX action and configures it with a custom frontend type
 * to enable the schedule-sync functionality in the datagrid UI.
 */
class ScheduleSyncAction extends AjaxAction
{
    /**
     * @return array
     */
    #[\Override]
    public function getOptions()
    {
        $options = parent::getOptions();
        $options['frontend_type'] = 'schedule-sync';

        return $options;
    }
}
