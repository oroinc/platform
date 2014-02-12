<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelValue;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Event\CreateMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\MergeEvents;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MetadataFactory
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param ConfigProvider           $configProvider
     * @param DoctrineHelper           $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ConfigProvider $configProvider,
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->configProvider  = $configProvider;
        $this->doctrineHelper  = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Create merge entity metadata
     *
     * @param string $className
     * @return EntityMetadata
     * @throws InvalidArgumentException
     */
    public function createMergeMetadata($className)
    {
        $entityMergeConfig = $this->configProvider->getConfig($className);

        if (!$entityMergeConfig) {
            throw new InvalidArgumentException(sprintf('Merge config for "%s" is not exist.', $className));
        }

        $fieldsMetadata = array_merge(
            $this->createFieldsMetadata($className),
            $this->createMappedOutsideFieldsMetadataByDoctrineMetadata($className),
            $this->createMappedOutsideFieldsMetadataByConfig($className)
        );

        $metadata = new EntityMetadata(
            $entityMergeConfig->all(),
            $fieldsMetadata,
            new DoctrineMetadata($className, (array)$this->doctrineHelper->getDoctrineMetadataFor($className))
        );

        $this->eventDispatcher->dispatch(
            MergeEvents::CREATE_METADATA,
            new CreateMetadataEvent($metadata)
        );

        return $metadata;
    }

    /**
     * Create merge entity fields metadata
     *
     * @param string $className
     * @return FieldMetadata[]
     */
    public function createFieldsMetadata($className)
    {
        $fieldsMetadata   = [];
        $doctrineMetadata = $this->doctrineHelper->getDoctrineMetadataFor($className);
        $configs          = $this->configProvider->getConfigs($className);

        if ($configs) {
            /* @var ConfigInterface $config */
            foreach ($configs as $config) {
                $fieldName = $config->getId()->getFieldName();

                if ($options = $config->all()) {
                    if ($doctrineMetadata->hasAssociation($fieldName)) {
                        $fieldMapping = $doctrineMetadata->getAssociationMapping($fieldName);
                    } else {
                        $fieldMapping = $doctrineMetadata->getFieldMapping($fieldName);
                    }

                    $fieldsMetadata[$fieldName] = $this->createFieldMetadata(
                        $options,
                        $this->createDoctrineMetadata($className, $fieldMapping)
                    );
                }
            }
        }

        return $fieldsMetadata;
    }

    /**
     * Get metadata from doctrine relations ref-one and ref-many
     * outside of the entity by config
     *
     * @param string $className
     * @return FieldMetadata[]
     */
    public function createMappedOutsideFieldsMetadataByConfig($className)
    {
        $fieldsMetadata = [];
        $repository     = $this
            ->doctrineHelper
            ->getEntityRepository('Oro\Bundle\EntityConfigBundle\Entity\ConfigModelValue');
        $configs        = $repository->findBy(['code' => 'merge_relation_enable']);

        if (!$configs) {
            return $fieldsMetadata;
        }

        /* @var ConfigModelValue $config */
        foreach ($configs as $config) {
            $field           = $config->getField();
            $fieldName       = $field->getFieldName();
            $entity          = $field->getEntity();
            $entityClassName = $entity->getClassName();

            $doctrineMetadata = $this->doctrineHelper->getDoctrineMetadataFor($entityClassName);

            if ($doctrineMetadata->hasAssociation($fieldName)) {
                $relatedClassName      = $doctrineMetadata->getName();
                $fieldMapping          = $doctrineMetadata->getAssociationMapping($fieldName);
                $fieldDoctrineMetadata = $this->createDoctrineMetadata($className, $fieldMapping);

                if ($fieldDoctrineMetadata->get('targetEntity') == $className) {
                    $fieldConfig = $this->configProvider->getConfig($entityClassName, $fieldName);

                    $uniqueFieldName = $this->createUniqueFieldName(
                        $relatedClassName,
                        $fieldName
                    );

                    $fieldMetadata = $this->createFieldMetadata(
                        $fieldConfig->all(),
                        $fieldDoctrineMetadata
                    );

                    $fieldMetadata->set('field_name', $uniqueFieldName);
                    $fieldsMetadata[$uniqueFieldName] = $fieldMetadata;
                }
            }
        }

        return $fieldsMetadata;
    }

    /**
     * Get metadata from doctrine relations ref-one and ref-many
     * outside of the entity by doctrine metadta
     *
     * @param string $className
     * @return FieldMetadata[]
     */
    public function createMappedOutsideFieldsMetadataByDoctrineMetadata($className)
    {
        $fieldsMetadata = [];
        $allMetadata    = $this->doctrineHelper->getAllMetadata();

        if (!$allMetadata) {
            return $fieldsMetadata;
        }

        foreach ($allMetadata as $metadata) {
            $fieldsMapping = $metadata->getAssociationsByTargetClass($className);
            if ($fieldsMapping) {
                foreach ($fieldsMapping as $fieldMapping) {
                    $relatedClassName      = $metadata->getName();
                    $fieldDoctrineMetadata = $this->createDoctrineMetadata(
                        $className,
                        $fieldMapping
                    );

                    if ($fieldDoctrineMetadata->has('mappedBy')) {
                        continue;
                    }

                    $fieldMetadata = $this->createFieldMetadata(
                        ['hidden' => true],
                        $fieldDoctrineMetadata
                    );

                    $uniqueFieldName = $this->createUniqueFieldName(
                        $relatedClassName,
                        $fieldMetadata->getFieldName()
                    );

                    $fieldMetadata->set(
                        'field_name',
                        $uniqueFieldName
                    );

                    $fieldsMetadata[$uniqueFieldName] = $fieldMetadata;
                }
            }
        }

        return $fieldsMetadata;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return string
     */
    protected function createUniqueFieldName($className, $fieldName)
    {
        return str_replace('\\', '_', $className) . '_' . $fieldName;
    }

    /**
     * @param array            $options
     * @param DoctrineMetadata $doctrineMetadata
     * @return FieldMetadata
     */
    protected function createFieldMetadata(array $options, DoctrineMetadata $doctrineMetadata = null)
    {
        return new FieldMetadata($options, $doctrineMetadata);
    }

    /**
     * @param string $classMame
     * @param array  $fieldMapping
     * @return DoctrineMetadata
     */
    protected function createDoctrineMetadata($classMame, array $fieldMapping)
    {
        return new DoctrineMetadata($classMame, $fieldMapping);
    }
}
