<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Exception\UnhandledErrorsException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks if there are any errors in the context,
 * and if so, throws UnhandledErrorsException exception.
 */
class AssertNotHasErrors implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$context->hasErrors()) {
            // no errors
            return;
        }

        throw new UnhandledErrorsException($context->getErrors());
    }
}
