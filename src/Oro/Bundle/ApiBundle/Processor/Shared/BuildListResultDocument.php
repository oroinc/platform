<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;

/**
 * Builds response based on the context state
 * and adds the response document builder to the context.
 */
class BuildListResultDocument extends BuildResultDocument
{
    /**
     * {@inheritdoc}
     */
    protected function processResult(DocumentBuilderInterface $documentBuilder, Context $context)
    {
        $documentBuilder->setDataCollection(
            $context->getResult(),
            $context->getRequestType(),
            $context->getMetadata()
        );
    }
}
