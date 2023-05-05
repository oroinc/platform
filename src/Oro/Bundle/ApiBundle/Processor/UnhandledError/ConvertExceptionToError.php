<?php

namespace Oro\Bundle\ApiBundle\Processor\UnhandledError;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts an exception that should be processed by the "unhandled_error" action to an API error
 * and adds it to the context.
 */
class ConvertExceptionToError implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$context->hasResult()) {
            return;
        }

        $exception = $context->getResult();
        if (!$exception instanceof \Throwable) {
            throw new RuntimeException(sprintf(
                'The result should be an instance of Throwable, "%s" given.',
                get_debug_type($exception)
            ));
        }

        if (!$exception instanceof \Exception) {
            $exception = new \ErrorException(
                $exception->getMessage(),
                $exception->getCode(),
                E_ERROR,
                $exception->getFile(),
                $exception->getLine(),
                $exception
            );
        }
        $context->addError(Error::createByException($exception));
        $context->removeResult();
    }
}
