<?php

namespace Oro\Bundle\TranslationBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;
use Symfony\Component\HttpFoundation\Request;

class ResetTranslationsMassAction extends AbstractMassAction
{
    /** @var array */
    protected $requiredOptions = ['data_identifier'];

    /**
     * {@inheritDoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['handler'])) {
            $options['handler'] = 'oro_translation.mass_action.reset_translation_handler';
        }

        if (empty($options['route'])) {
            $options['route'] = 'oro_translation_mass_reset';
        }

        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajax';
        }

        if (empty($options['route_parameters'])) {
            $options['route_parameters'] = [];
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

    /**
     * {@inheritdoc}
     */
    protected function getRequestType()
    {
        return Request::METHOD_POST;
    }
}
