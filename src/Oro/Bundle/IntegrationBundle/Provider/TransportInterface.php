<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;

interface TransportInterface extends TransportTypeInterface
{
    /**
     * @param ParameterBag $settings
     * @return mixed
     */
    public function init(ParameterBag $settings);

    /**
     * @param string $action
     * @param array $params
     * @return mixed
     */
    public function call($action, array $params = []);
}
