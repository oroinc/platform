<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;

class MetadataBuilder
{
    /**
     * @var MetadataFactory
     */
    protected $metadataFactory;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param MetadataFactory $metadataFactory
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        MetadataFactory $metadataFactory,
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Build merge entity metadata by class
     *
     * @param string $className
     * @return EntityMetadata
     */
    public function createEntityMetadataByClass($className)
    {
        $classMetadata = $this->doctrineHelper->getMetadataFor($className);

        $result = $this->metadataFactory->createEntityMetadata(array(), (array) $classMetadata);

        $this->addDoctrineFields($result, $classMetadata);
        $this->addDoctrineAssociations($result, $classMetadata);
        $this->addDoctrineInverseAssociations($result, $classMetadata, $className);

        $this->eventDispatcher->dispatch(
            MergeEvents::BUILD_METADATA,
            new EntityMetadataEvent($result)
        );

        return $result;
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param ClassMetadata $classMetadata
     */
    protected function addDoctrineFields(EntityMetadata $entityMetadata, ClassMetadata $classMetadata)
    {
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $fieldMetadata = $this->metadataFactory->createFieldMetadata(
                array(
                    'field_name' => $fieldName,
                ),
                $classMetadata->getFieldMapping($fieldName)
            );
            $entityMetadata->addFieldMetadata($fieldMetadata);
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param ClassMetadata $classMetadata
     */
    protected function addDoctrineAssociations(EntityMetadata $entityMetadata, ClassMetadata $classMetadata)
    {
        foreach ($classMetadata->getAssociationMappings() as $fieldName => $associationMapping) {
            $fieldMetadata = $this->metadataFactory->createFieldMetadata(
                array('field_name' => $fieldName),
                $associationMapping
            );
            $entityMetadata->addFieldMetadata($fieldMetadata);
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param ClassMetadata $classMetadata
     * @param string $className
     */
    protected function addDoctrineInverseAssociations(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        $className
    ) {
        $allMetadata = $this->doctrineHelper->getAllMetadata();

        foreach ($allMetadata as $metadata) {
            if ($metadata == $classMetadata) {
                // Skip own class metadata
                continue;
            }

            $currentClassName = $metadata->getName();
            $associationMappings = $metadata->getAssociationsByTargetClass($className);

            foreach ($associationMappings as $fieldName => $associationMapping) {
                if ((isset($associationMapping['type']) &&
                    $associationMapping['type'] === ClassMetadataInfo::MANY_TO_MANY) ||
                    isset($associationMapping['mappedBy'])
                ) {
                    // Skip "mapped by" and many-to-many as it's included on other side.
                    continue;
                }

                $associationMapping['mappedBySourceEntity'] = false;

                $fieldMetadata = $this->metadataFactory->createFieldMetadata(
                    array(
                        'field_name' => $this->createInverseAssociationFieldName($currentClassName, $fieldName),
                        'merge_modes' => array(MergeModes::UNITE),
                        'source_field_name' => $fieldName,
                        'source_class_name' => $currentClassName,
                    ),
                    $associationMapping
                );

                $entityMetadata->addFieldMetadata($fieldMetadata);
            }
        }
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return string
     */
    protected function createInverseAssociationFieldName($className, $fieldName)
    {
        return str_replace('\\', '_', $className) . '_' . $fieldName;
    }
}
