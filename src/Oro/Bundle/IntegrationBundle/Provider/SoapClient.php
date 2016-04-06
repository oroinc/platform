<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

class SoapClient extends \SoapClient
{
    /**
     * {@inheritdoc}
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $response = parent::__doRequest($request, $location, $action, $version, $one_way);

        // Remove all non printable characters except whitespace characters
        $response = preg_replace('/[^[:print:][:space:]]/u', '', $response);

        return $response;
    }
}
