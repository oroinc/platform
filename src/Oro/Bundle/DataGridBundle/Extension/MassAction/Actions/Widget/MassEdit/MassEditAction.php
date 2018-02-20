<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Widget\MassEdit;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Widget\WindowMassAction;
use Symfony\Component\HttpFoundation\Request;

class MassEditAction extends WindowMassAction
{
    /** @var array */
    protected $requiredOptions = ['handler', 'route', 'data_identifier'];

    /**
     * {@inheritDoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = 'edit-mass';
        }

        return parent::setOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedRequestTypes()
    {
        return [Request::METHOD_POST];
    }
}
