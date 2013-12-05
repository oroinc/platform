<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;

class SimpleTransport implements TransportInterface
{
    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel()
    {
        return 'oro.integration.transport.simple';
    }

    /**
     * Returns entity name needed to store transport settings
     *
     * @return string
     */
    public function getSettingsEntityFQCN()
    {
        return null;
    }

    /**
     * @param ParameterBag $settings
     * @return mixed
     */
    public function init(ParameterBag $settings)
    {
        return true;
    }

    /**
     * @param string $action
     * @param array $params
     * @return mixed
     */
    public function call($action, array $params = [])
    {
        return [];
    }

    /**
     * Returns form type name needed to setup transport
     *
     * @return string
     */
    public function getSettingsFormType()
    {
        return 'hidden';
    }
}
