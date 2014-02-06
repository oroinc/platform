<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

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
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param ConfigManager $configManager
     * @param EntityManager $entityManager
     */
    public function __construct(ConfigManager $configManager, EntityManager $entityManager)
    {
        $this->configManager = $configManager;
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $className
     */
    public function getMergeMetadata($className)
    {
        $fieldMetadata     = [];
        $mergeProvider     = $this->configManager->getProvider('merge');
        $entityMergeConfig = $mergeProvider->getConfig($className);
        /* @var \Doctrine\ORM\Mapping\ClassMetadata $doctrineMetadata */
        $doctrineMetadata = $this->entityManager->getMetadataFactory()->getMetadataFor($className);

        foreach ($mergeProvider->getConfigs($className) as $config) {
            /* @var \Oro\Bundle\EntityConfigBundle\Config\Config $config */
            $fieldType = $config->getId()->getFieldType();
            $fieldName = $config->getId()->getFieldName();

            if ($options = $config->all()) {
                if (in_array($fieldType, self::$relationFieldTypes)) {
                    $fieldDoctrineMetadata = new DoctrineMetadata($doctrineMetadata->associationMappings[$fieldName]);
                    $mergeMetadata         = new CollectionMetadata($options);
                } else {
                    $fieldDoctrineMetadata = new DoctrineMetadata($doctrineMetadata->fieldMappings[$fieldName]);
                    $mergeMetadata         = new FieldMetadata($options);
                }

                $mergeMetadata->set(DoctrineMetadata::OPTION_NAME, $fieldDoctrineMetadata);
                $fieldMetadata[] = $mergeMetadata;
            }
        }

        $entityDoctrineMetadata = new DoctrineMetadata((array)$doctrineMetadata);
        $metadata               = new EntityMetadata($entityMergeConfig->all(), $fieldMetadata);
        $metadata->set(DoctrineMetadata::OPTION_NAME, $entityDoctrineMetadata);

        return $metadata;
    }
}
