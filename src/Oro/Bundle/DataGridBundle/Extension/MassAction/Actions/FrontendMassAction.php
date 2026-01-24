<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

/**
 * Represents a generic frontend mass action for datagrids.
 *
 * This mass action provides a base for custom frontend-specific bulk operations that require
 * specialized client-side handling beyond the standard mass action types.
 */
class FrontendMassAction extends AbstractMassAction
{
    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = 'frontend-mass';
        }

        return parent::setOptions($options);
    }
}
