<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Widget;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

class WindowMassAction extends WidgetMassAction
{
    /**
     * {@inheritDoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_handle_type'])) {
            $options['frontend_handle_type'] = 'dialog';
        }

        return parent::setOptions($options);
    }
}
