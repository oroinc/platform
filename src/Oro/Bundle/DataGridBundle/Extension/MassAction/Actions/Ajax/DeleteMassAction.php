<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents a mass delete action for datagrid rows.
 *
 * This specialized AJAX mass action handles bulk deletion of multiple selected records,
 * with built-in support for confirmation dialogs and appropriate frontend handling.
 */
class DeleteMassAction extends AjaxMassAction
{
    /** @var array */
    protected $requiredOptions = ['handler', 'entity_name', 'data_identifier'];

    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['handler'])) {
            $options['handler'] = 'oro_datagrid.extension.mass_action.handler.delete';
        }

        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = 'delete-mass';
        }

        return parent::setOptions($options);
    }

    #[\Override]
    protected function getAllowedRequestTypes()
    {
        return [Request::METHOD_POST, Request::METHOD_DELETE];
    }
}
