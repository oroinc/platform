<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;

/**
 * Checks if there are any errors in the Context,
 * and if so, raises an exception for the first error.
 */
class ProcessErrors implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if (!$context->hasErrors()) {
            // no errors
            return;
        }

        $errors = $context->getErrors();
        $error  = $errors[0];

        $exception = $error->getInnerException();
        if (null !== $exception) {
            $underlyingException = ExceptionUtil::getProcessorUnderlyingException($exception);
            if ($underlyingException instanceof HttpExceptionInterface) {
                $exception = $underlyingException;
            }
        } else {
            $message = $error->getDetail();
            if (empty($message)) {
                $message = $error->getTitle();
            }
            if (empty($message)) {
                $message = 'Unknown error.';
            }
            $exception = new \RuntimeException($message);
        }

        throw $exception;
    }
}
