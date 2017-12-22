<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Utils\NonPrintableCharsStringSanitizer;

class NonPrintableCharsSanitizedSoapClient extends \SoapClient
{
    /**
     * {@inheritDoc}
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $response = parent::__doRequest($request, $location, $action, $version, $one_way);

        $response = $this->removeNonPrintableCharacters($response);

        return $response;
    }

    /**
     * {@inheritDoc}
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
                        $item = $this->removeNonPrintableCharacters($item);
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

    /**
     * @param string $string
     *
     * @return string|null
     */
    private function removeNonPrintableCharacters($string)
    {
        return $this->getSanitizer()->removeNonPrintableCharacters($string);
    }

    /**
     * @return NonPrintableCharsStringSanitizer
     */
    private function getSanitizer()
    {
        return new NonPrintableCharsStringSanitizer();
    }
}
