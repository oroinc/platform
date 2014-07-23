<?php

namespace Oro\Bundle\IntegrationBundle\Exception;

class SoapConnectionException extends TransportException
{
    /**
     * @param string     $response
     * @param \Exception $exception
     * @param string     $request
     * @param string     $headers
     *
     * @return static
     */
    public static function createFromResponse($response, \Exception $exception = null, $request = '', $headers = '')
    {
        $message = PHP_EOL;
        $message .= '[message] ' . (!empty($exception) ? $exception->getMessage() : '') . PHP_EOL;
        $message .= '[request] ' . $request . PHP_EOL;
        $message .= '[response] ' . $response . PHP_EOL;
        $message .= '[headers] ' . (!empty($headers['code']) ? $headers['code'] : '') . PHP_EOL;
        $message .= PHP_EOL;

        return new static($message, (!empty($exception) ? $exception->getCode() : 0), $exception);
    }
}
