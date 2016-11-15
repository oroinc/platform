<?php

namespace Oro\Bundle\UserBundle\Datagrid\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;

class ResetPasswordMassAction extends AbstractMassAction
{
    /** @var array */
    protected $defaultOptions = [
        'frontend_handle'  => 'redirect',
        'handler'          => 'oro_datagrid.mass_action.forced_password_reset.handler',
        'icon'             => 'unlock-alt',
        'frontend_type'    => 'dialog',
        'route'            => 'oro_user_mass_password_reset',
        'data_identifier'  => 'id',
        'route_parameters' => [],
    ];

    /**
     * {@inheritDoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['handler'])) {
            $options['handler'] = 'oro_datagrid.mass_action.forced_password_reset.handler';
        }

        if (empty($options['route'])) {
            $options['route'] = 'oro_user_mass_password_reset';
        }

        if (empty($options['route_parameters'])) {
            $options['route_parameters'] = [];
        }

        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajax';
        }

        return parent::setOptions($options);
    }
}
