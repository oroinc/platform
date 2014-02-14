<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelValue;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\MergeEvents;

class MetadataFactory
{
    const RELATION_OPTION_PREFIX = 'relation_';

    /**
     * @var ConfigProvider
     */
    protected $mergeConfigProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param ConfigProvider           $mergeConfigProvider
     * @param DoctrineHelper           $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ConfigProvider $mergeConfigProvider,
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->mergeConfigProvider  = $mergeConfigProvider;
        $this->doctrineHelper       = $doctrineHelper;
        $this->eventDispatcher      = $eventDispatcher;
    }

    /**
     * Create merge entity metadata
     *
     * @param string $className
     * @return EntityMetadata
     * @throws InvalidArgumentException
     */
    public function createEntityMetadata($className)
    {
        $entityMergeConfig = $this->mergeConfigProvider->getConfig($className);

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
            new DoctrineMetadata($className, (array)$this->doctrineHelper->getMetadataFor($className))
        );

        $this->eventDispatcher->dispatch(
            MergeEvents::CREATE_METADATA,
            new EntityMetadataEvent($metadata)
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
        $doctrineMetadata = $this->doctrineHelper->getMetadataFor($className);
        $configs          = $this->mergeConfigProvider->getConfigs($className);

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
                        $this->filterOptions($options),
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
        $repository     = $this->doctrineHelper
            ->getEntityRepository('Oro\Bundle\EntityConfigBundle\Entity\ConfigModelValue');
        $configs        = $repository->findBy(['scope' => 'merge', 'code' => 'relation_enable']);

        if (!$configs) {
            return $fieldsMetadata;
        }

        /* @var ConfigModelValue $config */
        foreach ($configs as $config) {
            $field           = $config->getField();
            $fieldName       = $field->getFieldName();
            $entity          = $field->getEntity();
            $entityClassName = $entity->getClassName();

            $doctrineMetadata = $this->doctrineHelper->getMetadataFor($entityClassName);

            if ($doctrineMetadata->hasAssociation($fieldName)) {
                $relatedClassName      = $doctrineMetadata->getName();
                $fieldMapping          = $doctrineMetadata->getAssociationMapping($fieldName);
                $fieldDoctrineMetadata = $this->createDoctrineMetadata($className, $fieldMapping);

                if ($fieldDoctrineMetadata->get('targetEntity') == $className) {
                    $fieldConfig = $this->mergeConfigProvider->getConfig($entityClassName, $fieldName);

                    $uniqueFieldName = $this->createUniqueFieldName(
                        $relatedClassName,
                        $fieldName
                    );

                    $fieldMetadata = $this->createFieldMetadata(
                        $this->filterOptions($fieldConfig->all(), true),
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
     * @param array $options
     * @param bool $fromRelation
     * @return array
     */
    protected function filterOptions(array $options, $fromRelation = false)
    {
        $result = array();
        foreach ($options as $key => $value) {
            if (0 === strpos($key, self::RELATION_OPTION_PREFIX)) {
                if ($fromRelation) {
                    $key = substr($key, strlen(self::RELATION_OPTION_PREFIX));
                } else {
                    break;
                }
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Get metadata from doctrine relations ref-one and ref-many
     * outside of the entity by doctrine metadata
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
            if ($metadata->getName() == $className) {
                continue;
            }
            $fieldsMapping = $metadata->getAssociationsByTargetClass($className);
            if ($fieldsMapping) {
                foreach ($fieldsMapping as $fieldMapping) {
                    $relatedClassName      = $metadata->getName();
                    $fieldDoctrineMetadata = $this->createDoctrineMetadata(
                        $className,
                        $fieldMapping
                    );

                    if ($fieldDoctrineMetadata->get('type') === ClassMetadataInfo::MANY_TO_MANY) {
                        // Skip many-to-many as it's included on other side.
                        continue;
                    }

                    if ($fieldDoctrineMetadata->has('mappedBy')) {
                        continue;
                    }

                    $fieldMetadata = $this->createFieldMetadata(
                        $this->filterOptions(['hidden' => true, 'merge_modes' => array(MergeModes::UNITE)]),
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
