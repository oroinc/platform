<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;

/**
 * Adds the included entities to the response using the response document builder.
 */
class AddIncludedObjectsToResultDocument implements ProcessorInterface
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

        $includedObjects = $context->getIncludedObjects();
        if (null === $includedObjects) {
            // no included objects
            return;
        }

        $documentBuilder = $context->getResponseDocumentBuilder();
        if (null === $documentBuilder) {
            // the response document builder is required to add included objects to the response body
            return;
        }

        foreach ($includedObjects as $object) {
            $objectData = $includedObjects->getData($object);
            $documentBuilder->addIncludedObject(
                $objectData->getNormalizedData(),
                $objectData->getMetadata()
            );
        }
    }
}
