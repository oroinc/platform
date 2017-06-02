<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

class TriggerEventAction extends AbstractAction
{
    /**
     * {@inheritdoc}
     */
    protected $requiredOptions = ['event_name', 'container'];

    /**
     * {@inheritDoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (!isset($options['container'])) {
            $options['container'] = '[data-role="grid-sidebar-component-container"]';
        }
        parent::setOptions($options);

        return $this;
    }
}
