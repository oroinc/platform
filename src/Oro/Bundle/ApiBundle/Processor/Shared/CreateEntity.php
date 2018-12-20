<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
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
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityLoader */
    protected $entityLoader;

    /** @var EntityInstantiator */
    protected $entityInstantiator;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param EntityLoader       $entityLoader
     * @param EntityInstantiator $entityInstantiator
     */
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
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // the entity already exists
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // for resource based on manageable entity the entity should be created, not a model
            $config = $context->getConfig();
            if (null !== $config) {
                $parentResourceClass = $config->getParentResourceClass();
                if ($parentResourceClass && $this->doctrineHelper->isManageableEntityClass($parentResourceClass)) {
                    $entityClass = $parentResourceClass;
                }
            }
        }

        $entityId = $context->getId();
        if ($entityId
            && $this->doctrineHelper->isManageableEntityClass($entityClass)
            && $this->isEntityExist($entityClass, $entityId, $context->getMetadata())
        ) {
            $context->addError(Error::createConflictValidationError('The entity already exists'));
        } else {
            $context->setResult($this->entityInstantiator->instantiate($entityClass));
        }
    }

    /**
     * @param string              $entityClass
     * @param mixed               $entityId
     * @param EntityMetadata|null $metadata
     *
     * @return bool
     */
    private function isEntityExist($entityClass, $entityId, ?EntityMetadata $metadata)
    {
        return
            null !== $metadata
            && !$metadata->hasIdentifierGenerator()
            && null !== $this->entityLoader->findEntity($entityClass, $entityId, $metadata);
    }
}
