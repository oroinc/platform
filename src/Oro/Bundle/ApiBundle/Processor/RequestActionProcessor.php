<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
            $exception = $e;
            $underlyingException = ExceptionUtil::getProcessorUnderlyingException($exception);

            $status = 0;
            if ($underlyingException instanceof HttpExceptionInterface
            ) {
                $exception = $underlyingException;
                $status = $underlyingException->getStatusCode();
            }

            if ($underlyingException instanceof AccessDeniedException) {
                $exception = $underlyingException;
                $status = $exception->getCode();
            }

            $error = new Error();

            $error->setStatus($status);
            $error->setDetail($exception->getMessage());
            $error->setInnerException($e);
            $context->addError($error);

            $context->setFirstGroup('normalize_result');
            parent::executeProcessors($context);
        }
    }
}
