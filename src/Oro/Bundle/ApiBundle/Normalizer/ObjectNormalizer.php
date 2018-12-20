<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\EntitySerializer\ConfigUtil;
use Oro\Component\EntitySerializer\DataAccessorInterface;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\SerializationHelper;

/**
 * Converts an object to an array.
 * This class should be synchronized with EntitySerializer, the difference between this class
 * and EntitySerializer is that this class does not load data from the database.
 * @see \Oro\Component\EntitySerializer\EntitySerializer
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ObjectNormalizer
{
    const MAX_NESTING_LEVEL = 1;

    /** @var ObjectNormalizerRegistry */
    protected $normalizerRegistry;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SerializationHelper */
    protected $serializationHelper;

    /** @var DataAccessorInterface */
    protected $dataAccessor;

    /** @var ConfigNormalizer */
    protected $configNormalizer;

    /** @var DataNormalizer */
    protected $dataNormalizer;

    /**
     * @param ObjectNormalizerRegistry $normalizerRegistry
     * @param DoctrineHelper           $doctrineHelper
     * @param SerializationHelper      $serializationHelper
     * @param DataAccessorInterface    $dataAccessor
     * @param ConfigNormalizer         $configNormalizer
     * @param DataNormalizer           $dataNormalizer
     */
    public function __construct(
        ObjectNormalizerRegistry $normalizerRegistry,
        DoctrineHelper $doctrineHelper,
        SerializationHelper $serializationHelper,
        DataAccessorInterface $dataAccessor,
        ConfigNormalizer $configNormalizer,
        DataNormalizer $dataNormalizer
    ) {
        $this->normalizerRegistry = $normalizerRegistry;
        $this->doctrineHelper = $doctrineHelper;
        $this->serializationHelper = $serializationHelper;
        $this->dataAccessor = $dataAccessor;
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
            throw new RuntimeException(sprintf(
                'The exclusion policy must be "%s". Object type: "%s".',
                ConfigUtil::EXCLUSION_POLICY_ALL,
                ClassUtils::getClass($object)
            ));
        }

        $result = [];
        $referenceFields = [];
        $entityClass = $this->getEntityClass($object);
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            $propertyPath = $field->getPropertyPath($fieldName);

            if (false !== strpos($propertyPath, ConfigUtil::PATH_DELIMITER)) {
                $referenceFields[$fieldName] = ConfigUtil::explodePropertyPath($propertyPath);
                continue;
            }

            $value = null;
            if ($this->dataAccessor->tryGetValue($object, $propertyPath, $value)) {
                if (null !== $value) {
                    $targetConfig = $field->getTargetEntity();
                    if (null !== $targetConfig) {
                        $value = $this->normalizeValue($value, $level + 1, $context, $targetConfig);
                    } else {
                        $value = $this->serializationHelper->transformValue(
                            $entityClass,
                            $fieldName,
                            $value,
                            $context,
                            $field
                        );
                    }
                }
                $result[$fieldName] = $value;
            } elseif ($this->isMetadataProperty($propertyPath)) {
                $result[$fieldName] = $this->getMetadataProperty($entityClass, $propertyPath);
            }
        }

        if (!empty($referenceFields)) {
            $result = $this->serializationHelper->handleFieldsReferencedToChildFields(
                $result,
                $entityClass,
                $config,
                $context,
                $referenceFields
            );
            $result = $this->handleFieldsReferencedToChildFields($result, $object, $referenceFields);
        }

        $postSerializeHandler = $config->getPostSerializeHandler();
        if (null !== $postSerializeHandler) {
            $result = $this->serializationHelper->postSerialize(
                $result,
                $postSerializeHandler,
                $context
            );
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function normalizeValue($value, $level, array $context, EntityDefinitionConfig $config = null)
    {
        if (is_array($value)) {
            $nextLevel = $level + 1;
            foreach ($value as $key => $val) {
                $value[$key] = $this->normalizeValue($val, $nextLevel, $context, $config);
            }
            if (null !== $config) {
                $value = $this->serializationHelper->processPostSerializeCollection($value, $config, $context);
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
                if (null !== $config) {
                    $result = $this->serializationHelper->processPostSerializeCollection($result, $config, $context);
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
     * @param array  $serializedData
     * @param object $object
     * @param array  $fields
     *
     * @return array
     */
    protected function handleFieldsReferencedToChildFields(array $serializedData, $object, array $fields)
    {
        foreach ($fields as $fieldName => $propertyPath) {
            if (\array_key_exists($fieldName, $serializedData)) {
                continue;
            }
            $lastFieldName = \array_pop($propertyPath);
            $currentObject = $object;
            foreach ($propertyPath as $currentFieldName) {
                $value = null;
                if ($this->dataAccessor->tryGetValue($currentObject, $currentFieldName, $value)) {
                    $currentObject = $value;
                } else {
                    $currentObject = null;
                }
                if (null === $currentObject) {
                    break;
                }
            }
            if (null !== $currentObject) {
                $value = null;
                if ($this->dataAccessor->tryGetValue($currentObject, $lastFieldName, $value)) {
                    $serializedData[$fieldName] = $value;
                }
            }
        }

        return $serializedData;
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
}
