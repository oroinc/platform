<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;

/**
 * Builds response based on the Context state
 * and add the response document builder to the Context.
 */
class BuildSingleItemResultDocument extends BuildResultDocument
{
    /**
     * {@inheritdoc}
     */
    protected function processResult(DocumentBuilderInterface $documentBuilder, Context $context)
    {
        $result = $context->getResult();
        if (null === $result) {
            $documentBuilder->setDataObject($result, $context->getRequestType());
        } else {
            $documentBuilder->setDataObject($result, $context->getRequestType(), $context->getMetadata());
        }
    }
}
