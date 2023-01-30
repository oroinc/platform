<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\AssociationMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads metadata for an entity.
 * This processor works with both ORM entities and plain objects.
 */
class LoadMetadata implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private ObjectMetadataLoader $objectMetadataLoader;
    private EntityMetadataLoader $entityMetadataLoader;
    private AssociationMetadataLoader $associationMetadataLoader;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ObjectMetadataLoader $objectMetadataLoader,
        EntityMetadataLoader $entityMetadataLoader,
        AssociationMetadataLoader $associationMetadataLoader
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->objectMetadataLoader = $objectMetadataLoader;
        $this->entityMetadataLoader = $entityMetadataLoader;
        $this->associationMetadataLoader = $associationMetadataLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var MetadataContext $context */

        if ($context->hasResult()) {
            // metadata is already loaded
            return;
        }

        $entityMetadata = null;
        $entityClass = $context->getClassName();
        $config = $context->getConfig();
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $entityMetadata = $this->entityMetadataLoader->loadEntityMetadata(
                $entityClass,
                $config,
                $context->getWithExcludedProperties(),
                $context->getTargetAction()
            );
        } else {
            $entityMetadata = $this->objectMetadataLoader->loadObjectMetadata(
                $entityClass,
                $config,
                $context->getWithExcludedProperties(),
                $context->getTargetAction()
            );
        }
        $this->associationMetadataLoader->completeAssociationMetadata($entityMetadata, $config, $context);
        $context->setResult($entityMetadata);
    }
}
