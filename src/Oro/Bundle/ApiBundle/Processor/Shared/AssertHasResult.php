<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ErrorStatusCodesWithoutContentTrait;

/**
 * Makes sure that the result exists in the Context.
 */
class AssertHasResult implements ProcessorInterface
{
    use ErrorStatusCodesWithoutContentTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if (!$context->hasResult()) {
            $responseStatusCode = $context->getResponseStatusCode();
            if (null === $responseStatusCode || !$this->isResponseWithoutContent($responseStatusCode)) {
                throw new RuntimeException('The result does not exist.');
            }
        }
    }
}
