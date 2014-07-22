<?php

namespace Oro\Bundle\IntegrationBundle\Exception;

class SoapConnectionException extends TransportException
{
    /**
     * @param \Exception $exception
     * @param string     $response
     * @param string     $request
     * @param string     $headers
     *
     * @return static
     */
    public static function createFromResponse(\Exception $exception, $response = '', $request = '', $headers = '')
    {
        $message = PHP_EOL;
        $message .= '[message] ' . $exception->getMessage() . PHP_EOL;
        $message .= '[request] ' . (!empty($request) ? $request : '') . PHP_EOL;
        $message .= '[response] ' . (!empty($response) ? $response : '') . PHP_EOL;
        $message .= '[headers] ' . (!empty($headers['code']) ? $headers['code'] : '') . PHP_EOL;
        $message .= PHP_EOL;

        return new static($message, $exception->getCode());
    }
}
