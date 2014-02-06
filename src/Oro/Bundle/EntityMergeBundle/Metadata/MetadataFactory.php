<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class MetadataFactory
{
    /**
     * @var array
     */
    protected static $relationFieldTypes = [
        'ref-one',
        'ref-many'
    ];

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
     * @param string $className
     *
     * @return EntityMetadata
     */
    public function getMergeMetadata($className)
    {
        $entityMergeConfig = $this->configProvider->getConfig($className);

        /* @var \Doctrine\ORM\Mapping\ClassMetadata $doctrineMetadata */
        $doctrineMetadata = $this
            ->entityManager
            ->getMetadataFactory()
            ->getMetadataFor($className);

        $fieldMetadata = array_merge(
            $this->getFieldsMetadata($className),
            $this->getRelatedMetadata($className)
        );

        $entityDoctrineMetadata = new DoctrineMetadata((array)$doctrineMetadata);
        $metadata               = new EntityMetadata($entityMergeConfig->all(), $fieldMetadata);
        $metadata->set(DoctrineMetadata::OPTION_NAME, $entityDoctrineMetadata);

        return $metadata;
    }

    /**
     * @param string $className
     *
     * @return FieldMetadata[]|CollectionMetadata[]
     */
    public function getFieldsMetadata($className)
    {
        $fieldMetadata = [];

        /* @var \Doctrine\ORM\Mapping\ClassMetadata $doctrineMetadata */
        $doctrineMetadata = $this
            ->entityManager
            ->getMetadataFactory()
            ->getMetadataFor($className);

        foreach ($this->configProvider->getConfigs($className) as $config) {
            /* @var \Oro\Bundle\EntityConfigBundle\Config\Config $config */
            $fieldType = $config->getId()->getFieldType();
            $fieldName = $config->getId()->getFieldName();

            if ($options = $config->all()) {
                if (in_array($fieldType, self::$relationFieldTypes)) {
                    $fieldMapping  = $doctrineMetadata->associationMappings[$fieldName];
                    $mergeMetadata = new CollectionMetadata($options);
                } else {
                    $fieldMapping  = $doctrineMetadata->fieldMappings[$fieldName];
                    $mergeMetadata = new FieldMetadata($options);
                }

                $fieldDoctrineMetadata = new DoctrineMetadata($fieldMapping);
                $mergeMetadata->set(DoctrineMetadata::OPTION_NAME, $fieldDoctrineMetadata);
                $fieldMetadata[] = $mergeMetadata;
            }
        }

        return $fieldMetadata;
    }

    /**
     * @param string $className
     *
     * @return RelationMetadata[]
     */
    public function getRelatedMetadata($className)
    {
        $relationMetadata        = [];
        $repository              = $this
            ->entityManager
            ->getRepository('OroEntityConfigBundle:ConfigModelValue');
        $configs                 = $repository->findBy(['code' => 'merge_relation_enable']);
        $doctrineMetadataFactory = $this
            ->entityManager
            ->getMetadataFactory();

        if ($configs) {
            foreach ($configs as $config) {
                $field           = $config->getField();
                $fieldName       = $field->getFieldName();
                $entity          = $field->getEntity();
                $entityClassName = $entity->getClassName();

                /* @var \Doctrine\ORM\Mapping\ClassMetadata $entityDoctrineMetadata */
                $entityDoctrineMetadata = $doctrineMetadataFactory->getMetadataFor($entityClassName);
                $fieldMapping           = $entityDoctrineMetadata->associationMappings[$fieldName];
                $fieldDoctrineMetadata  = new DoctrineMetadata($fieldMapping);

                if ($fieldDoctrineMetadata->get('targetEntity') == $className) {
                    $fieldConfig   = $this->configProvider->getConfig($entityClassName, $fieldName);
                    $mergeMetadata = new RelationMetadata($fieldConfig->all());

                    $mergeMetadata->set(DoctrineMetadata::OPTION_NAME, $fieldDoctrineMetadata);

                    $relationMetadata[] = $mergeMetadata;
                }
            }
        }

        return $relationMetadata;
    }
}
