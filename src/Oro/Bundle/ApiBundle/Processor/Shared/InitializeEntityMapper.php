<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
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
        /** @var FormContext $context */

        if (null !== $context->getEntityMapper()) {
            // the entity mapper is already initialized
            return;
        }

        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
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
