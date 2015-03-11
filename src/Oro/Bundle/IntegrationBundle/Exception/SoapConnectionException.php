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
        $exceptionMessage = null !== $exception ? $exception->getMessage() : '';
        $exceptionCode    = null !== $exception ? $exception->getCode() : 0;
        $code             = !empty($headers['code']) ? $headers['code'] : 'unknown';

        $message = PHP_EOL;
        $message .= str_pad('[message]', 20, ' ', STR_PAD_RIGHT) . $exceptionMessage . PHP_EOL;
        $message .= str_pad('[request]', 20, ' ', STR_PAD_RIGHT) . $request . PHP_EOL;
        $message .= str_pad('[response]', 20, ' ', STR_PAD_RIGHT) . $response . PHP_EOL;
        $message .= str_pad('[code]', 20, ' ', STR_PAD_RIGHT) . $code . PHP_EOL;
        $message .= PHP_EOL;

        $exception = new static($message, $exceptionCode, $exception);
        $exception->setFaultCode($code);

        return $exception;
    }
}
