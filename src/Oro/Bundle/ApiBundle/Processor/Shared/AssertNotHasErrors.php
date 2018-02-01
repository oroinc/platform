<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Exception\UnhandledErrorsException;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Checks if there are any errors in the Context,
 * and if so, throws UnhandledErrorsException exception.
 */
class AssertNotHasErrors implements ProcessorInterface
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

        throw new UnhandledErrorsException($context->getErrors());
    }
}
