<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Util\AclProtectedEntityLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Loads the parent entity from the database and adds it to the context.
 */
class LoadParentEntity implements ProcessorInterface
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

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        if ($context->hasParentEntity()) {
            // the parent entity is already loaded
            return;
        }

        $parentConfig = $context->getParentConfig();
        if (null === $parentConfig) {
            // unsupported API resource
            return;
        }

        $parentEntityClass = $context->getManageableParentEntityClass($this->doctrineHelper);
        if (!$parentEntityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        $parentMetadata = $context->getParentMetadata();
        if (null === $parentMetadata) {
            // unsupported API resource
            return;
        }

        try {
            $parentEntity = $this->entityLoader->findEntity(
                $parentEntityClass,
                $context->getParentId(),
                $parentConfig,
                $parentMetadata,
                $context->getRequestType()
            );
        } catch (AccessDeniedException) {
            throw new AccessDeniedException('No access to the parent entity.');
        }
        if (null !== $parentEntity) {
            $context->setParentEntity($parentEntity);
        }
    }
}
