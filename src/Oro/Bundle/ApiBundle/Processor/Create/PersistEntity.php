<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes new ORM entity persistent.
 */
class PersistEntity implements ProcessorInterface
{
    public const OPERATION_NAME = 'persist_new_entity';

    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        if ($context->isProcessed(self::OPERATION_NAME) || $context->isProcessed(SaveEntity::OPERATION_NAME)) {
            // the entity was already persisted or saved
            return;
        }

        if ($context->isExisting()) {
            // only a new entity need to be persistent
            return;
        }

        $entity = $context->getResult();
        if (!\is_object($entity)) {
            // entity does not exist
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($entity, false);
        if (null === $em || null === $context->getMetadata()) {
            // only manageable entities with API metadata are supported
            return;
        }

        $em->persist($entity);
        $context->setProcessed(self::OPERATION_NAME);
    }
}
