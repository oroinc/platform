<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;

class RequestActionProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function executeProcessors(ContextInterface $context)
    {
        try {
            parent::executeProcessors($context);
        } catch (ExecutionFailedException $e) {
            $underlyingException = ExceptionUtil::getProcessorUnderlyingException($e);
            if ($underlyingException instanceof HttpExceptionInterface) {
                $e = $underlyingException;
            }

            throw $e;
        }
    }
}
