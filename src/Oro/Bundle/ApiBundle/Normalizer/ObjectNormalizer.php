<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
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
    private const MAX_NESTING_LEVEL = 1;

    /** @var ObjectNormalizerRegistry */
    private $normalizerRegistry;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var SerializationHelper */
    private $serializationHelper;

    /** @var DataAccessorInterface */
    private $dataAccessor;

    /** @var ConfigNormalizer */
    private $configNormalizer;

    /** @var DataNormalizer */
    private $dataNormalizer;

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
     * @param array                       $objects The list of objects to be normalized
     * @param EntityDefinitionConfig|null $config  Normalization rules
     * @param array                       $context Options post serializers and data transformers have access to
     * @param bool                        $skipPostSerializationForPrimaryObjects
     *
     * @return array
     */
    public function normalizeObjects(
        array $objects,
        EntityDefinitionConfig $config = null,
        array $context = [],
        bool $skipPostSerializationForPrimaryObjects = false
    ): array {
        $normalizedObjects = [];
        if ($objects) {
            if (null === $config) {
                foreach ($objects as $key => $object) {
                    $normalizedObjects[$key] = $this->normalizeValue($object, 1, $context, $config);
                }
            } else {
                $config = $this->getNormalizedConfig($config);
                $processedObjects = [];
                foreach ($objects as $key => $object) {
                    $processedObjects[$key] = $this->normalizeObjectByConfig($object, 1, $config, $context);
                }
                if (!$skipPostSerializationForPrimaryObjects) {
                    $processedObjects = $this->serializationHelper->processPostSerializeItems(
                        $processedObjects,
                        $config,
                        $context
                    );
                }
                foreach ($processedObjects as $key => $object) {
                    $data = $this->dataNormalizer->normalizeData([$object], $config);
                    $normalizedObjects[$key] = reset($data);
                }
            }
        }

        return $normalizedObjects;
    }

    private function getNormalizedConfig(EntityDefinitionConfig $config): EntityDefinitionConfig
    {
        $normalizedConfig = clone $config;
        $this->configNormalizer->normalizeConfig($normalizedConfig);

        return $normalizedConfig;
    }

    /**
     * @param object $entity
     * @param int    $level
     * @param array  $context
     *
     * @return array
     */
    private function normalizeEntity($entity, int $level, array $context): array
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
    private function normalizePlainObject($object, int $level, array $context): array
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
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function normalizeObjectByConfig(
        $object,
        int $level,
        EntityDefinitionConfig $config,
        array $context
    ): array {
        if (!$config->isExcludeAll()) {
            throw new RuntimeException(sprintf(
                'The exclusion policy must be "%s". Object type: "%s".',
                ConfigUtil::EXCLUSION_POLICY_ALL,
                $this->doctrineHelper->getClass($object)
            ));
        }

        $result = [];
        $referenceFields = [];
        $entityClass = $this->getEntityClass($object);
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }

            $propertyPath = $field->getPropertyPath($fieldName);

            if (null === $field->getAssociationQuery()
                && false !== strpos($propertyPath, ConfigUtil::PATH_DELIMITER)
            ) {
                $referenceFields[$fieldName] = ConfigUtil::explodePropertyPath($propertyPath);
                continue;
            }

            $value = null;
            if ($this->tryGetValue($object, $propertyPath, $field, $value)) {
                if (null !== $value) {
                    $targetConfig = $field->getTargetEntity();
                    if (null !== $targetConfig) {
                        $value = $this->normalizeValue($value, $level + 1, $context, $targetConfig);
                    } else {
                        $value = $this->serializationHelper->transformValue($value, $context, $field);
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
                $config,
                $context,
                $referenceFields
            );
            $result = $this->handleFieldsReferencedToChildFields($result, $object, $referenceFields);
        }

        return $result;
    }

    /**
     * @param object                      $object
     * @param string                      $propertyName
     * @param EntityDefinitionFieldConfig $field
     * @param mixed                       $value
     *
     * @return bool
     */
    private function tryGetValue($object, string $propertyName, EntityDefinitionFieldConfig $field, &$value): bool
    {
        $associationQuery = $field->getAssociationQuery();
        if (null === $associationQuery) {
            return $this->dataAccessor->tryGetValue($object, $propertyName, $value);
        }

        $qb = clone $associationQuery;
        $qb->select('r');
        $entityIdFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNames($object);
        $entityId = $this->doctrineHelper->getEntityIdentifier($object);
        foreach ($entityIdFieldNames as $fieldName) {
            $qb
                ->andWhere(sprintf('e.%s = :id', $fieldName))
                ->setParameter($fieldName, $entityId[$fieldName]);
        }
        $value = $qb->getQuery()->getResult();

        return true;
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
    private function normalizeValue($value, int $level, array $context, EntityDefinitionConfig $config = null)
    {
        if (is_array($value)) {
            $nextLevel = $level + 1;
            foreach ($value as $key => $val) {
                $value[$key] = $this->normalizeValue($val, $nextLevel, $context, $config);
            }
            $value = $this->postSerializeCollection($value, $config, $context);
        } elseif (is_object($value)) {
            $objectNormalizer = $this->getObjectNormalizer($value, $context);
            if (null !== $objectNormalizer) {
                $value = $objectNormalizer->normalize($value, $context[ApiContext::REQUEST_TYPE]);
            } elseif ($value instanceof \Traversable) {
                $normalizedValues = [];
                $nextLevel = $level + 1;
                foreach ($value as $val) {
                    $normalizedValues[] = $this->normalizeValue($val, $nextLevel, $context, $config);
                }
                $value = $this->postSerializeCollection($normalizedValues, $config, $context);
            } elseif (null !== $config) {
                $value = $this->serializationHelper->postSerializeItem(
                    $this->normalizeObjectByConfig($value, $level, $config, $context),
                    $config,
                    $context
                );
            } elseif ($this->doctrineHelper->isManageableEntity($value)) {
                if ($level <= self::MAX_NESTING_LEVEL) {
                    $value = $this->normalizeEntity($value, $level, $context);
                } else {
                    $entityId = $this->doctrineHelper->getEntityIdentifier($value);
                    $count = count($entityId);
                    if ($count === 1) {
                        $value = reset($entityId);
                    } elseif ($count > 1) {
                        $value = $entityId;
                    } else {
                        throw new RuntimeException(sprintf(
                            'The entity "%s" does not have an identifier.',
                            $this->doctrineHelper->getClass($value)
                        ));
                    }
                }
            } elseif ($level <= self::MAX_NESTING_LEVEL) {
                $value = $this->normalizePlainObject($value, $level, $context);
            } elseif (method_exists($value, '__toString')) {
                $value = (string)$value;
            } else {
                throw new RuntimeException(sprintf(
                    'Instance of "%s" cannot be normalized.',
                    get_class($value)
                ));
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
    private function handleFieldsReferencedToChildFields(array $serializedData, $object, array $fields): array
    {
        foreach ($fields as $fieldName => $propertyPath) {
            if (array_key_exists($fieldName, $serializedData)) {
                continue;
            }
            $lastFieldName = array_pop($propertyPath);
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
    private function getEntityClass($object): string
    {
        return $object instanceof EntityIdentifier
            ? $object->getClass()
            : $this->doctrineHelper->getClass($object);
    }

    /**
     * Checks whether the given property path represents a metadata property
     */
    private function isMetadataProperty(string $propertyPath): bool
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
    private function getMetadataProperty(string $entityClass, string $propertyPath)
    {
        switch ($propertyPath) {
            case ConfigUtil::CLASS_NAME:
                return $entityClass;
            case ConfigUtil::DISCRIMINATOR:
                return $this->getEntityDiscriminator($entityClass);
        }

        return null;
    }

    private function getEntityDiscriminator(string $entityClass): ?string
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass, false);
        if (null === $metadata) {
            return null;
        }

        $map = array_flip($metadata->discriminatorMap);

        return $map[$entityClass];
    }

    private function postSerializeCollection(array $items, ?EntityDefinitionConfig $config, array $context): array
    {
        if (null === $config) {
            return $items;
        }

        return $this->serializationHelper->postSerializeCollection($items, $config, $context);
    }

    /**
     * @param object $object
     * @param array  $context
     *
     * @return ObjectNormalizerInterface|null
     */
    private function getObjectNormalizer($object, array $context): ?ObjectNormalizerInterface
    {
        if (!isset($context[ApiContext::REQUEST_TYPE])) {
            throw new \InvalidArgumentException(sprintf(
                'The object normalization context must have "%s" attribute.',
                ApiContext::REQUEST_TYPE
            ));
        }

        return $this->normalizerRegistry->getObjectNormalizer($object, $context[ApiContext::REQUEST_TYPE]);
    }
}
