<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AjaxAction;

class WorkflowActivateAction extends AjaxAction
{
    /**
     * @return ActionConfiguration
     */
    public function getOptions()
    {
        $options = parent::getOptions();

        $options['frontend_type'] = 'workflow-activate';

        return $options;
    }
}
