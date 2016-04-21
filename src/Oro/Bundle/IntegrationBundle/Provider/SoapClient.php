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

    /**
     * {@inheritdoc}
     */
    public function __soapCall(
        $function_name,
        $arguments,
        $options = null,
        $input_headers = null,
        &$output_headers = null
    ) {
        if (is_array($arguments)) {
            array_walk_recursive(
                $arguments,
                function (&$item) {
                    if (is_string($item)) {
                        // Remove all non printable characters except whitespace characters
                        $item = preg_replace('/[^[:print:][:space:]]/u', '', $item);
                    }
                }
            );
        }

        return parent::__soapCall(
            $function_name,
            $arguments,
            $options,
            $input_headers,
            $output_headers
        );
    }
}
