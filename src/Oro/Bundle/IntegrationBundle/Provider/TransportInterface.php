<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface TransportInterface
{
    /**
     * @param array $settings
     * @return mixed
     */
    public function init(array $settings);

    /**
     * @param $action
     * @param array $params
     * @return mixed
     */
    public function call($action, $params = []);
}
