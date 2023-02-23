<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;

/**
 * Provides a set of static methods to work with exception objects.
 */
class ExceptionUtil
{
    /**
     * Gets an exception that caused a processor failure.
     */
    public static function getProcessorUnderlyingException(\Exception $e): \Exception
    {
        $result = $e;
        while ($result instanceof ExecutionFailedException) {
            $result = $result->getPrevious();
        }
        if (null === $result) {
            $result = $e;
        }

        return $result;
    }
}
