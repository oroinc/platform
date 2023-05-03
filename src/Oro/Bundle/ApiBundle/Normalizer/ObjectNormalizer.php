<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Provider\AssociationAccessExclusionProviderInterface;
use Oro\Bundle\ApiBundle\Provider\AssociationAccessExclusionProviderRegistry;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Component\EntitySerializer\ConfigUtil;
use Oro\Component\EntitySerializer\DataAccessorInterface;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\SerializationHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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

    private ObjectNormalizerRegistry $normalizerRegistry;
    private DoctrineHelper $doctrineHelper;
    private SerializationHelper $serializationHelper;
    private DataAccessorInterface $dataAccessor;
    private ConfigNormalizer $configNormalizer;
    private DataNormalizer $dataNormalizer;
    private AuthorizationCheckerInterface $authorizationChecker;
    private AssociationAccessExclusionProviderRegistry $associationAccessExclusionProviderRegistry;

    public function __construct(
        ObjectNormalizerRegistry $normalizerRegistry,
        DoctrineHelper $doctrineHelper,
        SerializationHelper $serializationHelper,
        DataAccessorInterface $dataAccessor,
        ConfigNormalizer $configNormalizer,
        DataNormalizer $dataNormalizer,
        AuthorizationCheckerInterface $authorizationChecker,
        AssociationAccessExclusionProviderRegistry $associationAccessExclusionProviderRegistry
    ) {
        $this->normalizerRegistry = $normalizerRegistry;
        $this->doctrineHelper = $doctrineHelper;
        $this->serializationHelper = $serializationHelper;
        $this->dataAccessor = $dataAccessor;
        $this->configNormalizer = $configNormalizer;
        $this->dataNormalizer = $dataNormalizer;
        $this->authorizationChecker = $authorizationChecker;
        $this->associationAccessExclusionProviderRegistry = $associationAccessExclusionProviderRegistry;
    }

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

    private function normalizeEntity(object $entity, int $level, array $context): array
    {
        $result = [];
        /** @var ClassMetadata $metadata */
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

    private function normalizePlainObject(object $object, int $level, array $context): array
    {
        $result = [];
        $refl = new EntityReflectionClass($object);

        $nextLevel = $level + 1;

        $properties = $refl->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $result[$property->getName()] = $this->normalizeValue($property->getValue($object), $nextLevel, $context);
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function normalizeObjectByConfig(
        object $object,
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

        $associationAccessExclusionProvider = $this->associationAccessExclusionProviderRegistry
            ->getAssociationAccessExclusionProvider($context[ApiContext::REQUEST_TYPE]);

        $result = [];
        $referenceFields = [];
        $entityClass = $this->getEntityClass($object);
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass, false);
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }

            $propertyPath = $field->getPropertyPath($fieldName);

            if (null === $field->getAssociationQuery()
                && str_contains($propertyPath, ConfigUtil::PATH_DELIMITER)
            ) {
                $referenceFields[$fieldName] = ConfigUtil::explodePropertyPath($propertyPath);
                continue;
            }

            $value = null;
            if ($this->tryGetValue(
                $object,
                $propertyPath,
                $field,
                $metadata,
                $value,
                $associationAccessExclusionProvider
            )) {
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function tryGetValue(
        object $object,
        string $propertyName,
        EntityDefinitionFieldConfig $field,
        ?ClassMetadata $metadata,
        mixed &$value,
        AssociationAccessExclusionProviderInterface $associationAccessExclusionProvider
    ): bool {
        $associationQuery = $field->getAssociationQuery();
        if (null !== $associationQuery) {
            return $this->tryGetValueByAssociationQuery($object, $associationQuery, $field, $value);
        }

        $result = $this->dataAccessor->tryGetValue($object, $propertyName, $value);
        if ($result
            && null !== $metadata
            && $metadata->hasAssociation($propertyName)
            && !$associationAccessExclusionProvider->isIgnoreAssociationAccessCheck($metadata->name, $propertyName)
        ) {
            if ($metadata->isCollectionValuedAssociation($propertyName)) {
                if (($value instanceof Collection || \is_array($value)) && $this->hasInvisibleEntity($value)) {
                    $value = $this->removeInvisibleEntities($value);
                }
            } elseif (\is_object($value) && !$this->authorizationChecker->isGranted('VIEW', $value)) {
                $value = null;
            }
        }

        return $result;
    }

    private function hasInvisibleEntity(Collection|array $collection): bool
    {
        $hasInvisibleEntity = false;
        foreach ($collection as $item) {
            if (\is_object($item) && !$this->authorizationChecker->isGranted('VIEW', $item)) {
                $hasInvisibleEntity = true;
                break;
            }
        }

        return $hasInvisibleEntity;
    }

    private function removeInvisibleEntities(Collection|array $collection): array
    {
        $result = [];
        foreach ($collection as $item) {
            if (!\is_object($item) || $this->authorizationChecker->isGranted('VIEW', $item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    private function tryGetValueByAssociationQuery(
        object $object,
        QueryBuilder $associationQuery,
        EntityDefinitionFieldConfig $field,
        mixed &$value
    ): bool {
        $qb = clone $associationQuery;
        $qb->select('r');
        $entityIdFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNames($object);
        $entityId = $this->doctrineHelper->getEntityIdentifier($object);
        foreach ($entityIdFieldNames as $fieldName) {
            $qb
                ->andWhere(sprintf('e.%s = :id', $fieldName))
                ->setParameter($fieldName, $entityId[$fieldName]);
        }
        $result = $qb->getQuery()->getResult();
        if ($field->isCollectionValuedAssociation()) {
            $value = $result;
        } elseif ($result) {
            $value = reset($result);
        } else {
            $value = null;
        }

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function normalizeValue(
        mixed $value,
        int $level,
        array $context,
        EntityDefinitionConfig $config = null
    ): mixed {
        if (\is_array($value)) {
            $nextLevel = $level + 1;
            foreach ($value as $key => $val) {
                $value[$key] = $this->normalizeValue($val, $nextLevel, $context, $config);
            }
            $value = $this->postSerializeCollection($value, $config, $context);
        } elseif (\is_object($value)) {
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
                    $count = \count($entityId);
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
                    \get_class($value)
                ));
            }
        }

        return $value;
    }

    private function handleFieldsReferencedToChildFields(array $serializedData, object $object, array $fields): array
    {
        foreach ($fields as $fieldName => $propertyPath) {
            if (\array_key_exists($fieldName, $serializedData)) {
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
     */
    private function getEntityClass(object $object): string
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
     * Returns a value of a metadata property.
     */
    private function getMetadataProperty(string $entityClass, string $propertyPath): ?string
    {
        if (ConfigUtil::CLASS_NAME === $propertyPath) {
            return $entityClass;
        }
        if (ConfigUtil::DISCRIMINATOR === $propertyPath) {
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

    private function getObjectNormalizer(object $object, array $context): ?ObjectNormalizerInterface
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
