<?php

namespace Oro\Bundle\TranslationBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mass action for resetting translations in datagrids.
 *
 * Extends the base mass action to provide translation-specific reset functionality,
 * allowing users to reset multiple translation entries to their default values through
 * the datagrid interface. Configures default handler, route, and request method for
 * the reset operation.
 */
class ResetTranslationsMassAction extends AbstractMassAction
{
    /** @var array */
    protected $requiredOptions = ['data_identifier'];

    #[\Override]
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

    #[\Override]
    protected function getAllowedRequestTypes()
    {
        return [Request::METHOD_POST];
    }

    #[\Override]
    protected function getRequestType()
    {
        return Request::METHOD_POST;
    }
}
