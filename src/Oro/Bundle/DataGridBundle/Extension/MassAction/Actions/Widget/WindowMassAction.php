<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Widget;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

class WindowMassAction extends WidgetMassAction
{
    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'dialog';
        }

        return parent::setOptions($options);
    }
}
