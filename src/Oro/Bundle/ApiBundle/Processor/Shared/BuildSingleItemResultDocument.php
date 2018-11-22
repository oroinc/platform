<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;

/**
 * Builds the response based on the context state.
 */
class BuildSingleItemResultDocument extends BuildResultDocument
{
    /**
     * {@inheritdoc}
     */
    protected function processResult(DocumentBuilderInterface $documentBuilder, Context $context)
    {
        $documentBuilder->setDataObject(
            $context->getResult(),
            $context->getRequestType(),
            $context->getMetadata()
        );
    }
}
