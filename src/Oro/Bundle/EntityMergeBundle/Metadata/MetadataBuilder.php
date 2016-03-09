<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

class MetadataBuilder
{
    /** @var MetadataFactory */
    protected $metadataFactory;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var ConfigProvider */
    protected $entityExtendProvider;

    /**
     * @param MetadataFactory $metadataFactory
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     * @param ConfigProvider $entityExtendConfigProvider
     */
    public function __construct(
        MetadataFactory $metadataFactory,
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher,
        ConfigProvider $entityExtendConfigProvider
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityExtendProvider = $entityExtendConfigProvider;
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
        $this->addDoctrineInverseAssociations($result, $classMetadata);
        $this->addUnmappedDynamicFields($result, $classMetadata);

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
    protected function addUnmappedDynamicFields(EntityMetadata $entityMetadata, ClassMetadata $classMetadata)
    {
        $metadata = array_map(
            function ($field) {
                return $this->metadataFactory->createFieldMetadata([
                    'field_name' => $field,
                ]);
            },
            $this->getUnmappedDynamicFields($classMetadata)
        );

        array_map([$entityMetadata, 'addFieldMetadata'], $metadata);
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param ClassMetadata $classMetadata
     */
    protected function addDoctrineFields(EntityMetadata $entityMetadata, ClassMetadata $classMetadata)
    {
        $fields = array_diff(
            $classMetadata->getFieldNames(),
            $classMetadata->getIdentifierFieldNames(),
            $this->getDeletedFields($classMetadata->name)
        );

        foreach ($fields as $fieldName) {
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
     * @param string $className
     *
     * @return string[]
     */
    protected function getDeletedFields($className)
    {
        return array_map(
            function (ConfigInterface $config) {
                return $config->getId()->getFieldName();
            },
            array_filter(
                $this->entityExtendProvider->getConfigs($className),
                function (ConfigInterface $config) {
                    return $config->is('is_deleted');
                }
            )
        );
    }

    /**
     * @param ClassMetadata $classMetadata
     *
     * @return string[]
     */
    protected function getUnmappedDynamicFields(ClassMetadata $classMetadata)
    {
        return array_map(
            function (ConfigInterface $config) {
                return $config->getId()->getFieldName();
            },
            $this->getUnmappedDynamicFieldsConfigs($classMetadata)
        );
    }

    /**
     * @param ClassMetadata $classMetadata
     *
     * @return ConfigInterface[]
     */
    protected function getUnmappedDynamicFieldsConfigs(ClassMetadata $classMetadata)
    {
        return array_filter(
            $this->entityExtendProvider->getConfigs($classMetadata->name),
            function (ConfigInterface $config) use ($classMetadata) {
                return !$classMetadata->hasField($config->getId()->getFieldName()) &&
                    !$classMetadata->hasAssociation($config->getId()->getFieldName()) &&
                    !$config->is('is_deleted') &&
                    $config->is('owner', ExtendScope::OWNER_CUSTOM) &&
                    ExtendHelper::isFieldAccessible($config) &&
                    !in_array($config->getId()->getFieldType(), RelationType::$toAnyRelations, true) &&
                    (
                        !$config->has('target_entity') ||
                        ExtendHelper::isEntityAccessible(
                            $this->entityExtendProvider->getConfig($config->get('target_entity'))
                        )
                    );
            }
        );
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param ClassMetadata $classMetadata
     */
    protected function addDoctrineAssociations(EntityMetadata $entityMetadata, ClassMetadata $classMetadata)
    {
        $associations = array_diff(
            $classMetadata->getAssociationNames(),
            $this->getDeletedFields($classMetadata->name)
        );

        foreach ($associations as $fieldName) {
            $fieldMetadata = $this->metadataFactory->createFieldMetadata(
                ['field_name' => $fieldName],
                $classMetadata->getAssociationMapping($fieldName)
            );
            $entityMetadata->addFieldMetadata($fieldMetadata);
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param ClassMetadata  $classMetadata
     */
    protected function addDoctrineInverseAssociations(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata
    ) {
        $associationMappings = $this->doctrineHelper
            ->getInversedUnidirectionalAssociationMappings($classMetadata->name);

        foreach ($associationMappings as $fieldName => $associationMapping) {
            $mergeModes = [MergeModes::UNITE];
            if ($associationMapping['type'] === ClassMetadataInfo::ONE_TO_ONE) {
                // for fields with ONE_TO_ONE relation Unite strategy is impossible, so Replace is used
                $mergeModes = [MergeModes::REPLACE];
            }

            $fieldName = $associationMapping['fieldName'];
            $currentClassName = $associationMapping['sourceEntity'];

            $fieldMetadata = $this->metadataFactory->createFieldMetadata(
                array(
                    'field_name' => $this->createInverseAssociationFieldName($currentClassName, $fieldName),
                    'merge_modes' => $mergeModes,
                    'source_field_name' => $fieldName,
                    'source_class_name' => $currentClassName,
                ),
                $associationMapping
            );

            $entityMetadata->addFieldMetadata($fieldMetadata);
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
