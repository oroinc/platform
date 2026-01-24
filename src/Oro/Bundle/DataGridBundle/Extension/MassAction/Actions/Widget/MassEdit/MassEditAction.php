<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Widget\MassEdit;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Widget\WindowMassAction;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents a mass edit action for datagrid rows.
 *
 * This action opens a dialog window allowing users to edit common fields across multiple
 * selected records simultaneously, streamlining bulk data updates.
 */
class MassEditAction extends WindowMassAction
{
    /** @var array */
    protected $requiredOptions = ['handler', 'route', 'data_identifier'];

    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = 'edit-mass';
        }

        return parent::setOptions($options);
    }

    #[\Override]
    protected function getAllowedRequestTypes()
    {
        return [Request::METHOD_POST];
    }
}
