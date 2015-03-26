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
        $exceptionMessage = '';
        $exceptionCode = 0;
        $code = 'unknown';

        if (null !== $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionCode = $exception->getCode();
        }

        if (!empty($headers['code'])) {
            $code = $headers['code'];
        }

        $message = PHP_EOL;
        $message .= str_pad('[message]', 20, ' ', STR_PAD_RIGHT) . $exceptionMessage . PHP_EOL;
        $message .= str_pad('[request]', 20, ' ', STR_PAD_RIGHT) . $request . PHP_EOL;
        $message .= str_pad('[response]', 20, ' ', STR_PAD_RIGHT) . $response . PHP_EOL;
        $message .= str_pad('[code]', 20, ' ', STR_PAD_RIGHT) . $code . PHP_EOL;
        $message .= PHP_EOL;

        $newException = new static($message, $exceptionCode, $exception);
        if ($exception instanceof \SoapFault) {
            $newException->setFaultCode($exception->faultcode);
        }

        return $newException;
    }
}
