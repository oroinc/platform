<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\AclProtectedEntityLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads the entity from the database and adds it to the context.
 */
class LoadEntity implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private AclProtectedEntityLoader $entityLoader;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AclProtectedEntityLoader $entityLoader
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityLoader = $entityLoader;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // the entity is already loaded
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // unsupported API resource
            return;
        }

        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        $metadata = $context->getMetadata();
        if (null === $metadata) {
            // unsupported API resource
            return;
        }

        $entity = $this->entityLoader->findEntity(
            $entityClass,
            $context->getId(),
            $config,
            $metadata,
            $context->getRequestType()
        );
        if (null !== $entity) {
            $context->setResult($entity);
        }
    }
}
