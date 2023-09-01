<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets a created entity identifier into the context.
 */
class SetEntityIdToContext implements ProcessorInterface
{
    public const OPERATION_NAME = 'set_entity_id_to_context';

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the entity identifier was already set
            return;
        }

        if ($context->isExisting()) {
            // the setting of an entity identifier to the context is needed only for a new entity
            return;
        }

        $entity = $context->getResult();
        if (null === $entity) {
            // the entity does not exist
            return;
        }

        $metadata = $context->getMetadata();
        if (null === $metadata || !$metadata->getIdentifierFieldNames()) {
            // the metadata does not exist or the entity does not have identifier field(s)
            return;
        }

        $id = $metadata->getIdentifierValue($entity);
        if (null !== $id) {
            $context->setId($id);
        }
        $context->setProcessed(self::OPERATION_NAME);
    }
}
