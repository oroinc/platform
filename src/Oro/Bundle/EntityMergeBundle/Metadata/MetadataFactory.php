<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelValue;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

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
     * Create merge entity metadata
     *
     * @param string $className
     * @return EntityMetadata
     */
    public function createMergeMetadata($className)
    {
        $entityMergeConfig = $this->configProvider->getConfig($className);

        if (!$entityMergeConfig) {
            throw new InvalidArgumentException(sprintf('Config for "%s" not exists', $className));
        }

        /* @var ClassMetadata $doctrineMetadata */
        $doctrineMetadata = $this
            ->entityManager
            ->getMetadataFactory()
            ->getMetadataFor($className);

        $fieldsMetadata = array_merge(
            $this->createFieldsMetadata($className),
            $this->createRelationMetadata($className)
        );

        $entityDoctrineMetadata = new DoctrineMetadata((array)$doctrineMetadata);
        $metadata               = new EntityMetadata($entityMergeConfig->all(), $fieldsMetadata);
        $metadata->set(DoctrineMetadata::OPTION_NAME, $entityDoctrineMetadata);

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
        $fieldMetadata = [];

        /* @var \Doctrine\ORM\Mapping\ClassMetadata $doctrineMetadata */
        $doctrineMetadata = $this
            ->entityManager
            ->getMetadataFactory()
            ->getMetadataFor($className);

        $configs = $this->configProvider->getConfigs($className);

        if ($configs) {
            /* @var ConfigInterface $config */
            foreach ($configs as $config) {
                $fieldType = $config->getId()->getFieldType();
                $fieldName = $config->getId()->getFieldName();

                if ($options = $config->all()) {
                    if (in_array($fieldType, self::$relationFieldTypes)) {
                        $fieldMapping  = $doctrineMetadata->getAssociationMapping($fieldName);
                        $mergeMetadata = new CollectionMetadata($options);
                    } else {
                        $fieldMapping  = $doctrineMetadata->getFieldMapping($fieldName);
                        $mergeMetadata = new FieldMetadata($options);
                    }

                    $fieldDoctrineMetadata = new DoctrineMetadata($fieldMapping);
                    $mergeMetadata->set(DoctrineMetadata::OPTION_NAME, $fieldDoctrineMetadata);
                    $fieldMetadata[] = $mergeMetadata;
                }
            }
        }

        return $fieldMetadata;
    }

    /**
     * Get metadata from doctrine relations ref-one and ref-many outside of the entity
     *
     * @param string $className
     * @return RelationMetadata[]
     */
    public function createRelationMetadata($className)
    {
        $relationMetadata        = [];
        $repository              = $this->entityManager->getRepository('OroEntityConfigBundle:ConfigModelValue');
        $configs                 = $repository->findBy(['code' => 'merge_relation_enable']);
        $doctrineMetadataFactory = $this->entityManager->getMetadataFactory();

        if ($configs) {
            /* @var ConfigModelValue $config */
            foreach ($configs as $config) {
                $field           = $config->getField();
                $fieldName       = $field->getFieldName();
                $entity          = $field->getEntity();
                $entityClassName = $entity->getClassName();

                /* @var \Doctrine\ORM\Mapping\ClassMetadata $entityDoctrineMetadata */
                $entityDoctrineMetadata = $doctrineMetadataFactory->getMetadataFor($entityClassName);
                $fieldMapping           = $entityDoctrineMetadata->getAssociationMapping($fieldName);
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
