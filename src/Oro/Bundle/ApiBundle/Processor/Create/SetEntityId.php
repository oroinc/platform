<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether entity identifier exists in the context,
 * and if so, sets it to the entity.
 */
class SetEntityId implements ProcessorInterface
{
    /** @var EntityIdHelper */
    protected $entityIdHelper;

    /**
     * @param EntityIdHelper $entityIdHelper
     */
    public function __construct(EntityIdHelper $entityIdHelper)
    {
        $this->entityIdHelper = $entityIdHelper;
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

        $metadata = $context->getMetadata();
        if (null === $metadata) {
            // the metadata does not exist
            return;
        }

        if ($metadata->hasIdentifierGenerator()) {
            // ignore entities with an identity generator
            return;
        }

        $this->entityIdHelper->setEntityIdentifier($entity, $entityId, $metadata);
    }
}
