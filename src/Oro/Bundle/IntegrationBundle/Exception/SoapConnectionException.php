<?php

namespace Oro\Bundle\IntegrationBundle\Exception;

use Oro\Bundle\IntegrationBundle\Utils\SecureErrorMessageHelper;

/**
 * Exception that represents connection issues of the soap transport
 */
class SoapConnectionException extends TransportException
{
    /**
     * @param string          $response
     * @param \Exception|null $exception
     * @param string          $request
     * @param int|string      $code
     * @return static
     */
    public static function createFromResponse($response, \Exception $exception = null, $request = '', $code = 'unknown')
    {
        $exceptionMessage = '';
        $exceptionCode = 0;

        if (null !== $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionCode = $exception->getCode();
        }

        $message = PHP_EOL;
        $message .= str_pad('[message]', 20, ' ', STR_PAD_RIGHT) . $exceptionMessage . PHP_EOL;
        $message .= str_pad('[request]', 20, ' ', STR_PAD_RIGHT) . $request . PHP_EOL;
        $message .= str_pad('[response]', 20, ' ', STR_PAD_RIGHT) . $response . PHP_EOL;
        $message .= str_pad('[code]', 20, ' ', STR_PAD_RIGHT) . $code . PHP_EOL;
        $message .= PHP_EOL;

        $message = SecureErrorMessageHelper::sanitizeSecureInfo($message);

        $newException = new static($message, $exceptionCode, $exception);
        if ($exception instanceof \SoapFault) {
            $newException->setFaultCode($exception->faultcode);
        }

        return $newException;
    }
}
