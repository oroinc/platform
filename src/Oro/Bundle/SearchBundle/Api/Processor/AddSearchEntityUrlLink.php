<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Metadata\PropertyLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "entityUrl" link to the response of the search API resource
 * and hide the "entityUrl" field from the response.
 */
class AddSearchEntityUrlLink implements ProcessorInterface
{
    private const LINK_NAME = 'entityUrl';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var MetadataContext $context */

        $entityMetadata = $context->getResult();
        if (null === $entityMetadata) {
            // metadata is not loaded
            return;
        }

        $field = $entityMetadata->getPropertyByPropertyPath('entityUrl');
        if (null !== $field) {
            $field->setHidden();
            if (!$entityMetadata->hasLink(self::LINK_NAME)) {
                $entityMetadata->addLink(self::LINK_NAME, new PropertyLinkMetadata($field->getName()));
            }
        }
    }
}
