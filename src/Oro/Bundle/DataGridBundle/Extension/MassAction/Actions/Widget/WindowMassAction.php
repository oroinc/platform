<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Widget;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

/**
 * Represents a dialog window-based mass action for datagrids.
 *
 * This specialized widget mass action opens a modal dialog window to handle bulk operations,
 * providing a focused interface for user interaction with selected records.
 */
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
