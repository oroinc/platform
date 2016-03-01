<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDocument;

class ValidateResult implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $result = $context->getResult();
        if (array_key_exists(JsonApiDocument::DATA, $result) && !is_array($result[JsonApiDocument::DATA])) {
            throw new \RuntimeException(
                sprintf('The "%s" section must be an array.', JsonApiDocument::DATA)
            );
        }
    }
}
