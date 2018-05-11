<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * If the response document builder exists in the context
 * uses it to get the response body and then remove it from the context.
 */
class SetResultDocument implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $documentBuilder = $context->getResponseDocumentBuilder();
        if (null !== $documentBuilder) {
            $context->setResult($documentBuilder->getDocument());
            $context->setResponseDocumentBuilder(null);
        }
    }
}
