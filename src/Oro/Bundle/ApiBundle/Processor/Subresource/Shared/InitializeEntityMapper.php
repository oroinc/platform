<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Creates new instance of the entity mapper and adds it to the context.
 */
class InitializeEntityMapper implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private EntityInstantiator $entityInstantiator;
    private EntityOverrideProviderRegistry $entityOverrideProviderRegistry;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityInstantiator $entityInstantiator,
        EntityOverrideProviderRegistry $entityOverrideProviderRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityInstantiator = $entityInstantiator;
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        if (null !== $context->getEntityMapper()) {
            // the entity mapper is already initialized
            return;
        }

        $parentEntityClass = $context->getManageableParentEntityClass($this->doctrineHelper);
        if (!$parentEntityClass) {
            // the entity mapper is required only for a manageable parent entity
            // or the parent resource based on a manageable entity
            return;
        }

        $context->setEntityMapper(
            new EntityMapper(
                $this->doctrineHelper,
                $this->entityInstantiator,
                $this->entityOverrideProviderRegistry->getEntityOverrideProvider($context->getRequestType())
            )
        );
    }
}
