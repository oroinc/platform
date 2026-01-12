<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AjaxAction;

/**
 * Represents a datagrid action for deactivating workflows.
 *
 * This action extends the base AJAX action to provide workflow-specific deactivation functionality
 * with the frontend type set to 'workflow-deactivate'.
 */
class WorkflowDeactivateAction extends AjaxAction
{
    /**
     * @return ActionConfiguration
     */
    #[\Override]
    public function getOptions()
    {
        $options = parent::getOptions();

        $options['frontend_type'] = 'workflow-deactivate';

        return $options;
    }
}
