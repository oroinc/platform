<?php

namespace Oro\Bundle\ApiBundle\Util;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;

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
     * Gets the HTTP status code corresponding the given exception.
     *
     * @param \Exception $e
     *
     * @return int
     */
    public static function getExceptionStatusCode(\Exception $e)
    {
        $underlyingException = self::getProcessorUnderlyingException($e);
        if ($underlyingException instanceof HttpExceptionInterface) {
            $statusCode = $underlyingException->getStatusCode();
        } elseif ($underlyingException instanceof AccessDeniedException) {
            $statusCode = $underlyingException->getCode();
        } elseif ($underlyingException instanceof ForbiddenException) {
            $statusCode = Response::HTTP_FORBIDDEN;
        } else {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return $statusCode;
    }
}
