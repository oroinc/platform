<?php

namespace Oro\Bundle\ApiBundle\Processor\Create\Rest;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Removes the "Location" response header if any error occurs.
 */
class RemoveLocationHeader implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasErrors()
            && $context->getResponseHeaders()->has(SetLocationHeader::RESPONSE_HEADER_NAME)
        ) {
            $context->getResponseHeaders()->remove(SetLocationHeader::RESPONSE_HEADER_NAME);
        }
    }
}
