<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AjaxAction;

/**
 * Represents a datagrid action for activating workflows.
 *
 * This action extends the base AJAX action to provide workflow-specific activation functionality
 * with the frontend type set to 'workflow-activate'.
 */
class WorkflowActivateAction extends AjaxAction
{
    /**
     * @return ActionConfiguration
     */
    #[\Override]
    public function getOptions()
    {
        $options = parent::getOptions();

        $options['frontend_type'] = 'workflow-activate';

        return $options;
    }
}
