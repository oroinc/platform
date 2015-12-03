<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionUtil
{
    /**
     * Gets an exception that caused a processor failure.
     *
     * @param \Exception $e
     *
     * @return \Exception
     */
    public static function getProcessorUnderlyingException(\Exception $e)
    {
        $result = $e;
        while (null !== $result && $result instanceof ExecutionFailedException) {
            $result = $result->getPrevious();
        }

        return null !== $result
            ? $result
            : $e;
    }

    /**
     * Gets Http code for given exception.
     *
     * @param \Exception $e
     *
     * @return int
     */
    public static function getExceptionHttpCode(\Exception $e)
    {
        $code = Response::HTTP_INTERNAL_SERVER_ERROR;

        $underlyingException = self::getProcessorUnderlyingException($e);
        if ($underlyingException instanceof HttpExceptionInterface
        ) {
            $code = $underlyingException->getStatusCode();
        }

        if ($underlyingException instanceof AccessDeniedException) {
            $code = $underlyingException->getCode();
        }

        return $code;
    }
}
