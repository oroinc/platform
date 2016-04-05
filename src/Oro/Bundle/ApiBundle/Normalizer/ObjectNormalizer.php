<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Doctrine\Common\Util\ClassUtils;

use Oro\Component\EntitySerializer\DataAccessorInterface;
use Oro\Component\EntitySerializer\DataTransformerInterface;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\EntitySerializer\FieldConfig;

class ObjectNormalizer
{
    const MAX_NESTING_LEVEL = 1;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var DataAccessorInterface */
    protected $dataAccessor;

    /** @var DataTransformerInterface */
    protected $dataTransformer;

    /** @var array */
    private $normalizers = [];

    /** @var ObjectNormalizerInterface[]|null */
    private $sortedNormalizers;

    /**
     * @param DoctrineHelper           $doctrineHelper
     * @param DataAccessorInterface    $dataAccessor
     * @param DataTransformerInterface $dataTransformer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        DataAccessorInterface $dataAccessor,
        DataTransformerInterface $dataTransformer
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->dataAccessor = $dataAccessor;
        $this->dataTransformer = $dataTransformer;
    }

    /**
     * Registers a normalizer for a specific object type
     *
     * @param ObjectNormalizerInterface $normalizer
     * @param int                       $priority
     */
    public function addNormalizer(ObjectNormalizerInterface $normalizer, $priority = 0)
    {
        $this->normalizers[$priority][] = $normalizer;
        $this->sortedNormalizers        = null;
    }

    /**
     * @param mixed             $object
     * @param EntityConfig|null $config
     *
     * @return mixed
     */
    public function normalizeObject($object, EntityConfig $config = null)
    {
        if (null !== $object) {
            $object = $this->normalizeValue($object, is_array($object) ? 0 : 1, $config);
        }

        return $object;
    }

    /**
     * @param object $entity
     * @param int    $level
     *
     * @return array
     */
    protected function normalizeEntity($entity, $level)
    {
        $result   = [];
        $metadata = $this->doctrineHelper->getEntityMetadata($entity);

        $nextLevel = $level + 1;

        $fields = $metadata->getFieldNames();
        foreach ($fields as $fieldName) {
            $value = null;
            if ($this->dataAccessor->tryGetValue($entity, $fieldName, $value)) {
                $result[$fieldName] = $this->normalizeValue($value, $nextLevel);
            }
        }
        $associations = $metadata->getAssociationNames();
        foreach ($associations as $fieldName) {
            $value = null;
            if ($this->dataAccessor->tryGetValue($entity, $fieldName, $value)) {
                $result[$fieldName] = $this->normalizeValue($value, $nextLevel);
            }
        }

        return $result;
    }

    /**
     * @param object $object
     * @param int    $level
     *
     * @return array
     */
    protected function normalizePlainObject($object, $level)
    {
        $result = [];
        $refl   = new \ReflectionClass($object);

        $nextLevel = $level + 1;

        $properties = $refl->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $result[$property->getName()] = $this->normalizeValue($property->getValue($object), $nextLevel);
        }

        return $result;
    }

    /**
     * @param object       $object
     * @param EntityConfig $config
     * @param int          $level
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function normalizeObjectByConfig($object, EntityConfig $config, $level)
    {
        if (!$config->isExcludeAll()) {
            throw new \RuntimeException(
                sprintf(
                    'The "%s" must be "%s".',
                    EntityDefinitionConfig::EXCLUSION_POLICY,
                    EntityDefinitionConfig::EXCLUSION_POLICY_ALL
                )
            );
        }

        $result = [];
        $entityClass = ClassUtils::getClass($object);
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }

            $value        = null;
            $propertyPath = $field->getPropertyPath() ?: $fieldName;
            if ($this->dataAccessor->tryGetValue($object, $propertyPath, $value) && null !== $value) {
                $targetEntity = $field->getTargetEntity();
                if (null !== $targetEntity) {
                    $childFieldNames = array_keys($targetEntity->getFields());
                    if ($field->isCollapsed() && count($childFieldNames) === 1) {
                        $childFieldName = reset($childFieldNames);
                        if ($value instanceof \Traversable) {
                            $childValue = [];
                            foreach ($value as $val) {
                                $childVal = null;
                                if ($this->dataAccessor->tryGetValue($val, $childFieldName, $childVal)) {
                                    $childValue = $this->transformValue($entityClass, $fieldName, $childValue, $field);
                                }
                                $childValue[] = $childVal;
                            }
                        } else {
                            $childValue = null;
                            if (!$this->dataAccessor->tryGetValue($value, $childFieldName, $childValue)) {
                                continue;
                            }
                            $childValue = $this->transformValue($entityClass, $fieldName, $childValue, $field);
                        }
                        $value = $childValue;
                    } else {
                        $value = $this->normalizeObjectByConfig($value, $targetEntity, $level + 1);
                    }
                } else {
                    $value = $this->transformValue($entityClass, $fieldName, $value, $field);
                }
            }
            $result[$fieldName] = $value;
        }

        $postSerializeHandler = $config->getPostSerializeHandler();
        if (null !== $postSerializeHandler) {
            $result = $this->postSerialize($result, $postSerializeHandler);
        }

        return $result;
    }

    /**
     * @param mixed             $value
     * @param int               $level
     * @param EntityConfig|null $config
     *
     * @return mixed
     */
    protected function normalizeValue($value, $level, EntityConfig $config = null)
    {
        if (is_array($value)) {
            $nextLevel = $level + 1;
            foreach ($value as &$val) {
                $val = $this->normalizeValue($val, $nextLevel, $config);
            }
        } elseif (is_object($value)) {
            $objectNormalizer = $this->getObjectNormalizer($value);
            if (null !== $objectNormalizer) {
                $value = $objectNormalizer->normalize($value);
            } elseif ($value instanceof \Traversable) {
                $result    = [];
                $nextLevel = $level + 1;
                foreach ($value as $val) {
                    $result[] = $this->normalizeValue($val, $nextLevel, $config);
                }
                $value = $result;
            } elseif (null !== $config) {
                $value = $this->normalizeObjectByConfig($value, $config, $level);
            } elseif ($this->doctrineHelper->isManageableEntity($value)) {
                if ($level <= static::MAX_NESTING_LEVEL) {
                    $value = $this->normalizeEntity($value, $level);
                } else {
                    $entityId = $this->doctrineHelper->getEntityIdentifier($value);
                    $count    = count($entityId);
                    if ($count === 1) {
                        $value = reset($entityId);
                    } elseif ($count > 1) {
                        $value = $entityId;
                    } else {
                        throw new \RuntimeException(
                            sprintf(
                                'The entity "%s" does not have an identifier.',
                                ClassUtils::getClass($value)
                            )
                        );
                    }
                }
            } else {
                if ($level <= static::MAX_NESTING_LEVEL) {
                    $value = $this->normalizePlainObject($value, $level);
                } elseif (method_exists($value, '__toString')) {
                    $value = (string)$value;
                } else {
                    throw new \RuntimeException(
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
     * @param object $object
     *
     * @return ObjectNormalizerInterface|null
     */
    protected function getObjectNormalizer($object)
    {
        if (null === $this->sortedNormalizers) {
            krsort($this->normalizers);
            $this->sortedNormalizers = call_user_func_array('array_merge', $this->normalizers);
        }

        foreach ($this->sortedNormalizers as $normalizer) {
            if ($normalizer->supports($object)) {
                return $normalizer;
            }
        }

        return null;
    }

    /**
     * @param string           $entityClass
     * @param string           $fieldName
     * @param mixed            $fieldValue
     * @param FieldConfig|null $fieldConfig
     *
     * @return mixed
     */
    protected function transformValue($entityClass, $fieldName, $fieldValue, FieldConfig $fieldConfig = null)
    {
        return $this->dataTransformer->transform(
            $entityClass,
            $fieldName,
            $fieldValue,
            null !== $fieldConfig ? $fieldConfig->toArray(true) : []
        );
    }

    /**
     * @param array    $item
     * @param callable $handler
     *
     * @return array
     */
    protected function postSerialize(array $item, $handler)
    {
        return call_user_func($handler, $item);
    }
}
