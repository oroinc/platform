<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Makes sure that the request type exists in the Context.
 */
class ValidateRequestTypeExists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->getRequestType()->isEmpty()) {
            throw new \RuntimeException('The type of a request must be set in the context.');
        }
    }
}
