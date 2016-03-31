<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\SingleItemUpdateContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts JSON API data to plain array.
 */
class NormalizeRequestData implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemUpdateContext $context */

        $requestData = $context->getRequestData();

        if (!array_key_exists(JsonApiDocumentBuilder::DATA, $requestData)) {
            // a request data is already normalized
            return;
        }

        $relations = [];
        if (array_key_exists(JsonApiDocumentBuilder::RELATIONSHIPS, $requestData[JsonApiDocumentBuilder::DATA])) {
            $relationsData = $requestData[JsonApiDocumentBuilder::DATA][JsonApiDocumentBuilder::RELATIONSHIPS];
            foreach ($relationsData as $relationName => $data) {
                $relations[$relationName] = $data[JsonApiDocumentBuilder::DATA][JsonApiDocumentBuilder::ID];
            }
        }
        $requestData = array_merge(
            $requestData[JsonApiDocumentBuilder::DATA][JsonApiDocumentBuilder::ATTRIBUTES],
            $relations
        );

        $context->setRequestData($requestData);
    }
}
