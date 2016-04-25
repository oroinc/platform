<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\BuildJsonApiDocument as ParentBuild;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder;

/**
 * Builds JSON API response based on the Context state.
 */
class BuildJsonApiDocument extends ParentBuild
{
    /**
     * {@inheritdoc}
     */
    protected function processResult(Context $context, JsonApiDocumentBuilder $documentBuilder)
    {
        $result = $context->getResult();
        if (empty($result)) {
            $documentBuilder->setDataCollection($result);
        } else {
            $documentBuilder->setDataCollection($result, $context->getMetadata());
        }
    }
}
