<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ProcessErrors implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context->hasErrors()) {
            // if context has no errors - we have nothing to do
            return;
        }

        $errors = $context->getErrors();
        $error = array_pop($errors);
        $exception = $error->getInnerException();
        $underlyingException = ExceptionUtil::getProcessorUnderlyingException($exception);
        if ($underlyingException instanceof HttpExceptionInterface) {
            $exception = $underlyingException;
        }

        throw $exception;
    }
}
