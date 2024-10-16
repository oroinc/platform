<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "emailThreadContextItemId" meta property to "activityTargets" association.
 */
class AddEmailThreadContextItemIdMetaProperty implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var MetadataContext $context */

        $entityMetadata = $context->getResult();
        if (null === $entityMetadata) {
            return;
        }

        $activityTargetsAssociationMetadata = $entityMetadata->getAssociation('activityTargets')?->getTargetMetadata();
        if (null === $activityTargetsAssociationMetadata) {
            return;
        }

        $activityTargetsAssociationMetadata->addMetaProperty(
            new MetaPropertyMetadata('emailThreadContextItemId', DataType::STRING)
        );
    }
}
