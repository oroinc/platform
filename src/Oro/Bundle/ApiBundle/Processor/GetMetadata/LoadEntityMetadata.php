<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class LoadEntityMetadata implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityMetadataFactory */
    protected $entityMetadataFactory;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param EntityMetadataFactory $entityMetadataFactory
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntityMetadataFactory $entityMetadataFactory)
    {
        $this->doctrineHelper        = $doctrineHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var MetadataContext $context */

        if ($context->hasResult()) {
            // metadata already loaded
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $classMetadata  = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $entityMetadata = $this->entityMetadataFactory->createEntityMetadata($classMetadata);

        $fields = $classMetadata->getFieldNames();
        foreach ($fields as $fieldName) {
            $entityMetadata->addField(
                $this->entityMetadataFactory->createFieldMetadata($classMetadata, $fieldName)
            );
        }

        $associations = $classMetadata->getAssociationNames();
        foreach ($associations as $associationName) {
            $entityMetadata->addAssociation(
                $this->entityMetadataFactory->createAssociationMetadata($classMetadata, $associationName)
            );
        }

        $context->setResult($entityMetadata);
    }
}
