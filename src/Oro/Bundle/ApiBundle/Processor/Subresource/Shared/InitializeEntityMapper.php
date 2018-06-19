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
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EntityInstantiator */
    private $entityInstantiator;

    /** @var EntityOverrideProviderRegistry */
    private $entityOverrideProviderRegistry;

    /**
     * @param DoctrineHelper                 $doctrineHelper
     * @param EntityInstantiator             $entityInstantiator
     * @param EntityOverrideProviderRegistry $entityOverrideProviderRegistry
     */
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
    public function process(ContextInterface $context)
    {
        /** @var ChangeRelationshipContext $context */

        if (null !== $context->getEntityMapper()) {
            // the entity mapper is already initialized
            return;
        }

        $parentEntityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getParentClassName(),
            $context->getParentConfig()
        );
        if (!$parentEntityClass) {
            // the entity mapper is required only for a manageable parent entity
            // or the parent resource based on a manageable entity
            return;
        }

        $entityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getClassName(),
            $context->getConfig()
        );
        if (!$entityClass) {
            // the entity mapper is required only for manageable entities
            // or resources based on manageable entities
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
