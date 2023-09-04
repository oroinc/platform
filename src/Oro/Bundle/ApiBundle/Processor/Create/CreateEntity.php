<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Creates new instance of the entity and adds it to the context.
 * If the entity type does not have id generator and an entity
 * with the specified identifier already exists then a validation error
 * is added to the context.
 */
class CreateEntity implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private EntityLoader $entityLoader;
    private EntityInstantiator $entityInstantiator;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityLoader $entityLoader,
        EntityInstantiator $entityInstantiator
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityLoader = $entityLoader;
        $this->entityInstantiator = $entityInstantiator;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        $entityClass = $context->getClassName();
        $config = $context->getConfig();
        if (null !== $config) {
            $formDataClass = $config->getFormOption('data_class');
            if ($formDataClass && $formDataClass !== $entityClass) {
                $entityClass = $formDataClass;
                // disable entity mapping
                $context->setEntityMapper(null);
            } elseif (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
                // for resource based on manageable entity the entity should be created, not a model
                $parentResourceClass = $config->getParentResourceClass();
                if ($parentResourceClass && $this->doctrineHelper->isManageableEntityClass($parentResourceClass)) {
                    $entityClass = $parentResourceClass;
                }
            }
        }

        if ($context->hasResult()) {
            // the entity already exists
            return;
        }

        if ($this->isEntityExist($entityClass, $context)) {
            $context->addError(Error::createConflictValidationError('The entity already exists.'));
        } else {
            $context->setResult($this->entityInstantiator->instantiate($entityClass));
        }
    }

    private function isEntityExist(string $entityClass, CreateContext $context): bool
    {
        $entityId = $context->getId();
        if (!$entityId) {
            return false;
        }

        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            return false;
        }

        $metadata = $context->getMetadata();

        return
            null !== $metadata
            && !$metadata->hasIdentifierGenerator()
            && null !== $this->entityLoader->findEntity($entityClass, $entityId, $metadata);
    }
}
