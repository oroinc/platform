<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;

/**
 * Builds the response based on the context state.
 */
class BuildSingleItemResultDocument extends BuildResultDocument
{
    /**
     * {@inheritdoc}
     */
    protected function processResult(DocumentBuilderInterface $documentBuilder, Context $context): void
    {
        $documentBuilder->setDataObject(
            $context->getResult(),
            $context->getRequestType(),
            $context instanceof FormContext ? $context->getNormalizedMetadata() : $context->getMetadata()
        );
    }
}
