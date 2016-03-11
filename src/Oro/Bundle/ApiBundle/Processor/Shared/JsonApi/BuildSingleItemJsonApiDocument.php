<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder;

/**
 * Builds JSON API response based on the Context state.
 */
class BuildSingleItemJsonApiDocument extends BuildJsonApiDocument
{
    /**
     * {@inheritdoc}
     */
    protected function processResult(Context $context, JsonApiDocumentBuilder $documentBuilder)
    {
        $result = $context->getResult();
        if (null === $result) {
            $documentBuilder->setDataObject($result);
        } else {
            $documentBuilder->setDataObject($result, $context->getMetadata());
        }

        $context->setResult($documentBuilder->getDocument());
    }
}
