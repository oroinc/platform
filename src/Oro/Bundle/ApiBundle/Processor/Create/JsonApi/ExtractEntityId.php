<?php

namespace Oro\Bundle\ApiBundle\Processor\Create\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Checks whether entity identifier exists in the request data,
 * and if so, adds it to the Context.
 */
class ExtractEntityId implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext|SingleItemContext $context */

        if (null !== $context->getId()) {
            // an entity id is already set
            return;
        }

        $requestData = $context->getRequestData();
        if (!array_key_exists(JsonApiDoc::DATA, $requestData)) {
            // unexpected request data or they are already normalized
            return;
        }

        $data = $requestData[JsonApiDoc::DATA];
        if (!array_key_exists(JsonApiDoc::ID, $data)) {
            // no entity id
            return;
        }

        $context->setId($data[JsonApiDoc::ID]);
    }
}
