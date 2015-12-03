<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

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
        throw $exception;
    }
}
