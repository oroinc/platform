<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Redirect;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;

/**
 * Represents a redirect-based mass action for datagrids.
 *
 * This mass action redirects the user to a specified route with selected record identifiers
 * as parameters, allowing for custom processing pages for bulk operations.
 */
class RedirectMassAction extends AbstractMassAction
{
    /** @var array */
    protected $requiredOptions = ['route'];

    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'redirect';
        }

        if (empty($options['route_parameters'])) {
            $options['route_parameters'] = [];
        }

        return parent::setOptions($options);
    }
}
