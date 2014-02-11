<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelValue;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class MetadataFactory
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param ConfigProvider $configProvider
     * @param EntityManager  $entityManager
     */
    public function __construct(ConfigProvider $configProvider, EntityManager $entityManager)
    {
        $this->configProvider = $configProvider;
        $this->entityManager  = $entityManager;
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
            $this->createMappedOutsideFieldsMetadata($className)
        );

        $metadata = new EntityMetadata(
            $entityMergeConfig->all(),
            $fieldsMetadata,
            new DoctrineMetadata($className, (array)$this->getDoctrineMetadataFor($className))
        );

        return $metadata;
    }

    /**
     * @param string $className
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected function getDoctrineMetadataFor($className)
    {
        return $this
            ->entityManager
            ->getMetadataFactory()
            ->getMetadataFor($className);
    }

    /**
     * Create merge entity fields metadata
     *
     * @param string $className
     * @return FieldMetadata[]
     */
    public function createFieldsMetadata($className)
    {
        $fieldsMetadata = [];

        $doctrineMetadata = $this->getDoctrineMetadataFor($className);

        $configs = $this->configProvider->getConfigs($className);

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

                    $fieldsMetadata[] = new FieldMetadata($options, new DoctrineMetadata($className, $fieldMapping));
                }
            }
        }

        return $fieldsMetadata;
    }

    /**
     * Get metadata from doctrine relations ref-one and ref-many outside of the entity
     *
     * @param string $className
     * @return FieldMetadata[]
     */
    public function createMappedOutsideFieldsMetadata($className)
    {
        $relationMetadata = [];
        $repository       = $this->entityManager->getRepository('OroEntityConfigBundle:ConfigModelValue');
        $configs          = $repository->findBy(['code' => 'merge_relation_enable']);

        if (!$configs) {
            return $relationMetadata;
        }

        /* @var ConfigModelValue $config */
        foreach ($configs as $config) {
            $field           = $config->getField();
            $fieldName       = $field->getFieldName();
            $entity          = $field->getEntity();
            $entityClassName = $entity->getClassName();

            $doctrineMetadata = $this->getDoctrineMetadataFor($entityClassName);

            if ($doctrineMetadata->hasAssociation($fieldName)) {
                $fieldMapping           = $doctrineMetadata->getAssociationMapping($fieldName);
                $fieldDoctrineMetadata  = new DoctrineMetadata($className, $fieldMapping);

                if ($fieldDoctrineMetadata->get('targetEntity') == $className) {
                    $fieldConfig        = $this->configProvider->getConfig($entityClassName, $fieldName);
                    $relationMetadata[] = new FieldMetadata($fieldConfig->all(), $fieldDoctrineMetadata);
                }
            }
        }

        return $relationMetadata;
    }
}
