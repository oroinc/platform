<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Checks whether entity identifier exists in the Context,
 * and if so, sets it to the entity.
 */
class SetEntityId implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $entityId = $context->getId();
        if (null === $entityId) {
            // an entity id does not exist
            return;
        }

        $entity = $context->getResult();
        if (!is_object($entity)) {
            // an entity does not exist or has an unexpected type
            return;
        }

        $metadata = $this->doctrineHelper->getEntityMetadataForClass($context->getClassName(), false);
        if (null === $metadata) {
            // only manageable entities are supported
            return;
        }

        if ($metadata->usesIdGenerator()) {
            // ignore entities with an identity generator
            return;
        }

        $this->doctrineHelper->setEntityIdentifier($entity, $entityId, $metadata);
    }
}
