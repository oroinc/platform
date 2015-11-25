<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;

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
}
