<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDocument;

/**
 * Makes sure that the "data" section in the result data is an array.
 */
class ValidateNormalizedResultDataIsArray implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context->hasResult()) {
            // exit because a result was not set in the context
            return;
        }

        $result = $context->getResult();
        if (array_key_exists(JsonApiDocument::DATA, $result) && !is_array($result[JsonApiDocument::DATA])) {
            throw new \RuntimeException(
                sprintf('The "%s" section must be an array.', JsonApiDocument::DATA)
            );
        }
    }
}
