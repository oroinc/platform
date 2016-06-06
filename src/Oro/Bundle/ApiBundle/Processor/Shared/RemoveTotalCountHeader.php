<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Removes the "X-Include-Total-Count" response header if any error occurs.
 */
class RemoveTotalCountHeader implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasErrors()
            && $context->getResponseHeaders()->has(SetTotalCountHeader::RESPONSE_HEADER_NAME)
        ) {
            $context->getResponseHeaders()->remove(SetTotalCountHeader::RESPONSE_HEADER_NAME);
        }
    }
}
