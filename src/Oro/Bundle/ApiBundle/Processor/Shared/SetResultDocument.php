<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * If the response document builder exists in the Context
 * use it to get the response body and then remove it from the Context.
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
