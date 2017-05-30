<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Doctrine\Common\Util\ClassUtils;

use Oro\Component\EntitySerializer\ConfigUtil;
use Oro\Component\EntitySerializer\DataAccessorInterface;
use Oro\Component\EntitySerializer\DataTransformerInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class ObjectNormalizer
{
    const MAX_NESTING_LEVEL = 1;

    /** @var ObjectNormalizerRegistry */
    protected $normalizerRegistry;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var DataAccessorInterface */
    protected $dataAccessor;

    /** @var DataTransformerInterface */
    protected $dataTransformer;

    /** @var ConfigNormalizer */
    protected $configNormalizer;

    /** @var DataNormalizer */
    protected $dataNormalizer;

    /**
     * @param ObjectNormalizerRegistry $normalizerRegistry
     * @param DoctrineHelper           $doctrineHelper
     * @param DataAccessorInterface    $dataAccessor
     * @param DataTransformerInterface $dataTransformer
     * @param ConfigNormalizer         $configNormalizer
     * @param DataNormalizer           $dataNormalizer
     */
    public function __construct(
        ObjectNormalizerRegistry $normalizerRegistry,
        DoctrineHelper $doctrineHelper,
        DataAccessorInterface $dataAccessor,
        DataTransformerInterface $dataTransformer,
        ConfigNormalizer $configNormalizer,
        DataNormalizer $dataNormalizer
    ) {
        $this->normalizerRegistry = $normalizerRegistry;
        $this->doctrineHelper = $doctrineHelper;
        $this->dataAccessor = $dataAccessor;
        $this->dataTransformer = $dataTransformer;
        $this->configNormalizer = $configNormalizer;
        $this->dataNormalizer = $dataNormalizer;
    }

    /**
     * @param mixed                       $object  An object to be normalized
     * @param EntityDefinitionConfig|null $config  Normalization rules
     * @param array                       $context Options post serializers and data transformers have access to
     *
     * @return mixed
     */
    public function normalizeObject($object, EntityDefinitionConfig $config = null, array $context = [])
    {
        if (null !== $object) {
            if (null !== $config) {
                $normalizedConfig = clone $config;
                $this->configNormalizer->normalizeConfig($normalizedConfig);
                $config = $normalizedConfig;
            }
            $object = $this->normalizeValue($object, is_array($object) ? 0 : 1, $context, $config);
            if (null !== $config) {
                $data = [$object];
                $data = $this->dataNormalizer->normalizeData($data, $config);
                $object = reset($data);
            }
        }

        return $object;
    }

    /**
     * @param object $entity
     * @param int    $level
     * @param array  $context
     *
     * @return array
     */
    protected function normalizeEntity($entity, $level, array $context)
    {
        $result = [];
        $metadata = $this->doctrineHelper->getEntityMetadata($entity);

        $nextLevel = $level + 1;

        $fields = $metadata->getFieldNames();
        foreach ($fields as $fieldName) {
            $value = null;
            if ($this->dataAccessor->tryGetValue($entity, $fieldName, $value)) {
                $result[$fieldName] = $this->normalizeValue($value, $nextLevel, $context);
            }
        }
        $associations = $metadata->getAssociationNames();
        foreach ($associations as $fieldName) {
            $value = null;
            if ($this->dataAccessor->tryGetValue($entity, $fieldName, $value)) {
                $result[$fieldName] = $this->normalizeValue($value, $nextLevel, $context);
            }
        }

        return $result;
    }

    /**
     * @param object $object
     * @param int    $level
     * @param array  $context
     *
     * @return array
     */
    protected function normalizePlainObject($object, $level, array $context)
    {
        $result = [];
        $refl = new \ReflectionClass($object);

        $nextLevel = $level + 1;

        $properties = $refl->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $result[$property->getName()] = $this->normalizeValue($property->getValue($object), $nextLevel, $context);
        }

        return $result;
    }

    /**
     * @param object                 $object
     * @param int                    $level
     * @param EntityDefinitionConfig $config
     * @param array                  $context
     *
     * @return array
     */
    protected function normalizeObjectByConfig($object, $level, EntityDefinitionConfig $config, $context)
    {
        if (!$config->isExcludeAll()) {
            throw new RuntimeException(
                sprintf(
                    'The "%s" must be "%s".',
                    EntityDefinitionConfig::EXCLUSION_POLICY,
                    EntityDefinitionConfig::EXCLUSION_POLICY_ALL
                )
            );
        }

        $result = [];
        $entityClass = $this->getEntityClass($object);
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            $propertyPath = $field->getPropertyPath($fieldName);
            if ($this->isMetadataProperty($propertyPath)) {
                $result[$propertyPath] = $this->getMetadataProperty($entityClass, $propertyPath);
            } else {
                $value = null;
                if ($this->dataAccessor->tryGetValue($object, $propertyPath, $value)) {
                    if (null !== $value) {
                        $targetEntity = $field->getTargetEntity();
                        if (null !== $targetEntity) {
                            $value = $this->normalizeValue($value, $level + 1, $context, $targetEntity);
                        } else {
                            $value = $this->transformValue($entityClass, $fieldName, $value, $context, $field);
                        }
                    }
                    $result[$propertyPath] = $value;
                }
            }
        }

        $postSerializeHandler = $config->getPostSerializeHandler();
        if (null !== $postSerializeHandler) {
            $result = $this->postSerialize($result, $postSerializeHandler, $context);
        }

        return $result;
    }

    /**
     * @param mixed                       $value
     * @param int                         $level
     * @param array                       $context
     * @param EntityDefinitionConfig|null $config
     *
     * @return mixed
     */
    protected function normalizeValue($value, $level, array $context, EntityDefinitionConfig $config = null)
    {
        if (is_array($value)) {
            $nextLevel = $level + 1;
            foreach ($value as &$val) {
                $val = $this->normalizeValue($val, $nextLevel, $context, $config);
            }
        } elseif (is_object($value)) {
            $objectNormalizer = $this->normalizerRegistry->getObjectNormalizer($value);
            if (null !== $objectNormalizer) {
                $value = $objectNormalizer->normalize($value);
            } elseif ($value instanceof \Traversable) {
                $result = [];
                $nextLevel = $level + 1;
                foreach ($value as $val) {
                    $result[] = $this->normalizeValue($val, $nextLevel, $context, $config);
                }
                $value = $result;
            } elseif (null !== $config) {
                $value = $this->normalizeObjectByConfig($value, $level, $config, $context);
            } elseif ($this->doctrineHelper->isManageableEntity($value)) {
                if ($level <= static::MAX_NESTING_LEVEL) {
                    $value = $this->normalizeEntity($value, $level, $context);
                } else {
                    $entityId = $this->doctrineHelper->getEntityIdentifier($value);
                    $count = count($entityId);
                    if ($count === 1) {
                        $value = reset($entityId);
                    } elseif ($count > 1) {
                        $value = $entityId;
                    } else {
                        throw new RuntimeException(
                            sprintf(
                                'The entity "%s" does not have an identifier.',
                                ClassUtils::getClass($value)
                            )
                        );
                    }
                }
            } else {
                if ($level <= static::MAX_NESTING_LEVEL) {
                    $value = $this->normalizePlainObject($value, $level, $context);
                } elseif (method_exists($value, '__toString')) {
                    $value = (string)$value;
                } else {
                    throw new RuntimeException(
                        sprintf(
                            'Instance of "%s" cannot be normalized.',
                            get_class($value)
                        )
                    );
                }
            }
        }

        return $value;
    }

    /**
     * @param string                           $entityClass
     * @param string                           $fieldName
     * @param mixed                            $fieldValue
     * @param array                            $context
     * @param EntityDefinitionFieldConfig|null $fieldConfig
     *
     * @return mixed
     */
    protected function transformValue(
        $entityClass,
        $fieldName,
        $fieldValue,
        array $context,
        EntityDefinitionFieldConfig $fieldConfig = null
    ) {
        return $this->dataTransformer->transform(
            $entityClass,
            $fieldName,
            $fieldValue,
            null !== $fieldConfig ? $fieldConfig->toArray(true) : [],
            $context
        );
    }

    /**
     * Gets the real class name of an entity.
     *
     * @param object $object
     *
     * @return string
     */
    protected function getEntityClass($object)
    {
        return $object instanceof EntityIdentifier
            ? $object->getClass()
            : ClassUtils::getClass($object);
    }

    /**
     * Checks whether the given property path represents a metadata property
     *
     * @param string $propertyPath
     *
     * @return mixed
     */
    protected function isMetadataProperty($propertyPath)
    {
        return ConfigUtil::CLASS_NAME === $propertyPath || ConfigUtil::DISCRIMINATOR === $propertyPath;
    }

    /**
     * Returns a value of a metadata property
     *
     * @param string $entityClass
     * @param string $propertyPath
     *
     * @return mixed
     */
    protected function getMetadataProperty($entityClass, $propertyPath)
    {
        switch ($propertyPath) {
            case ConfigUtil::CLASS_NAME:
                return $entityClass;
            case ConfigUtil::DISCRIMINATOR:
                return $this->getEntityDiscriminator($entityClass);
        }

        return null;
    }

    /**
     * @param string $entityClass
     *
     * @return string|null
     */
    protected function getEntityDiscriminator($entityClass)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass, false);
        if (null === $metadata) {
            return null;
        }

        $map = array_flip($metadata->discriminatorMap);

        return $map[$entityClass];
    }

    /**
     * @param array    $item
     * @param callable $handler
     * @param array    $context
     *
     * @return array
     */
    protected function postSerialize(array $item, $handler, array $context)
    {
        return call_user_func($handler, $item, $context);
    }
}
