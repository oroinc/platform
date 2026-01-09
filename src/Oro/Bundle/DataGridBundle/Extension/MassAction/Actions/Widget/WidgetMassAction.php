<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Widget;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;

/**
 * Represents a widget-based mass action for datagrids.
 *
 * This mass action opens a widget (modal, dialog, or embedded component) to handle bulk
 * operations, allowing for complex user interactions before processing selected records.
 */
class WidgetMassAction extends AbstractMassAction
{
    /** @var array */
    protected $requiredOptions = ['route', 'frontend_type'];

    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_options'])) {
            $options['frontend_options'] = [];
        }
        if (empty($options['route_parameters'])) {
            $options['route_parameters'] = [];
        }

        return parent::setOptions($options);
    }
}
