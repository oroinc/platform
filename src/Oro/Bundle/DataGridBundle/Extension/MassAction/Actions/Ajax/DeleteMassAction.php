<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

class DeleteMassAction extends AjaxMassAction
{
    /** @var array */
    protected $requiredOptions = ['handler', 'entity_name', 'data_identifier'];

    /**
     * {@inheritDoc}
     */
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
}
