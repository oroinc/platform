<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds the included entities to the response using the response document builder.
 */
class AddIncludedEntitiesToResultDocument implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $includedData = $context->getIncludedData();
        if (null === $includedData) {
            // there are no included data in the request
            return;
        }

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // no included entities
            return;
        }

        $documentBuilder = $context->getResponseDocumentBuilder();
        if (null === $documentBuilder) {
            // the response document builder is required to add included entities to the response body
            return;
        }

        if (!$context->isSuccessResponse()) {
            // add included entities to the response body only if there was not any errors
            return;
        }

        $requestType = $context->getRequestType();
        foreach ($includedEntities as $entity) {
            $entityData = $includedEntities->getData($entity);
            $documentBuilder->addIncludedObject(
                $entityData->getNormalizedData(),
                $requestType,
                $entityData->getMetadata()
            );
        }
    }
}
