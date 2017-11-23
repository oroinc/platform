<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;

/**
 * Builds response based on the Context state
 * and add the response document builder to the Context.
 */
class BuildListResultDocument extends BuildResultDocument
{
    /**
     * {@inheritdoc}
     */
    protected function processResult(DocumentBuilderInterface $documentBuilder, Context $context)
    {
        $result = $context->getResult();
        if (empty($result)) {
            $documentBuilder->setDataCollection($result);
        } else {
            $documentBuilder->setDataCollection($result, $context->getMetadata());
        }
    }
}
