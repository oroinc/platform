<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes sure that the base structure of the result conforms JSON.API specification.
 */
class AssertResultSchema implements ProcessorInterface
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
        if (!is_array($result)) {
            throw new RuntimeException('The result must be an array.');
        }

        $rootSections = [JsonApiDoc::DATA, JsonApiDoc::ERRORS, JsonApiDoc::META];
        if (count(array_intersect(array_keys($result), $rootSections)) === 0) {
            throw new RuntimeException(
                sprintf(
                    'The result must contain at least one of the following sections: %s.',
                    implode(', ', $rootSections)
                )
            );
        }

        if (array_key_exists(JsonApiDoc::DATA, $result) && array_key_exists(JsonApiDoc::ERRORS, $result)) {
            throw new RuntimeException(
                sprintf(
                    'The sections "%s" and "%s" must not coexist in the result.',
                    JsonApiDoc::DATA,
                    JsonApiDoc::ERRORS
                )
            );
        }

        if (array_key_exists(JsonApiDoc::INCLUDED, $result) && !array_key_exists(JsonApiDoc::DATA, $result)) {
            throw new RuntimeException(
                sprintf(
                    'The result can contain the "%s" section only together with the "%s" section.',
                    JsonApiDoc::INCLUDED,
                    JsonApiDoc::DATA
                )
            );
        }
    }
}
