<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\AssociationMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Loads metadata for an entity.
 * This processor works with both ORM entities and plain objects.
 */
class LoadMetadata implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ObjectMetadataLoader */
    protected $objectMetadataLoader;

    /** @var EntityMetadataLoader */
    protected $entityMetadataLoader;

    /** @var AssociationMetadataLoader */
    protected $associationMetadataLoader;

    /**
     * @param DoctrineHelper            $doctrineHelper
     * @param ObjectMetadataLoader      $objectMetadataLoader
     * @param EntityMetadataLoader      $entityMetadataLoader
     * @param AssociationMetadataLoader $associationMetadataLoader
     */
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
    public function process(ContextInterface $context)
    {
        /** @var MetadataContext $context */

        if ($context->hasResult()) {
            // metadata is already loaded
            return;
        }

        $entityMetadata = null;
        $config = $context->getConfig();
        if ($this->doctrineHelper->isManageableEntityClass($context->getClassName())) {
            $entityMetadata = $this->entityMetadataLoader->loadEntityMetadata(
                $context->getClassName(),
                $config,
                $context->getWithExcludedProperties(),
                $context->getTargetAction()
            );
        } elseif ($config->hasFields()) {
            $entityMetadata = $this->objectMetadataLoader->loadObjectMetadata(
                $context->getClassName(),
                $config,
                $context->getWithExcludedProperties(),
                $context->getTargetAction()
            );
        }
        if (null !== $entityMetadata) {
            $this->associationMetadataLoader->completeAssociationMetadata($entityMetadata, $config, $context);
            $context->setResult($entityMetadata);
        }
    }
}
