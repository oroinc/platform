<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Doctrine\Common\Util\ClassUtils;

use Oro\Component\EntitySerializer\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class ObjectNormalizer
{
    const MAX_NESTING_LEVEL = 1;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var DataAccessorInterface */
    protected $dataAccessor;

    /** @var array */
    private $normalizers = [];

    /** @var ObjectNormalizerInterface[]|null */
    private $sortedNormalizers;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param DataAccessorInterface $dataAccessor
     */
    public function __construct(DoctrineHelper $doctrineHelper, DataAccessorInterface $dataAccessor)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->dataAccessor   = $dataAccessor;
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
     * @param mixed      $object
     * @param array|null $config
     *
     * @return mixed
     */
    public function normalizeObject($object, $config = null)
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
        foreach ($fields as $field) {
            $value = null;
            if ($this->dataAccessor->tryGetValue($entity, $field, $value)) {
                $result[$field] = $this->normalizeValue($value, $nextLevel);
            }
        }
        $associations = $metadata->getAssociationNames();
        foreach ($associations as $field) {
            $value = null;
            if ($this->dataAccessor->tryGetValue($entity, $field, $value)) {
                $result[$field] = $this->normalizeValue($value, $nextLevel);
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
     * @param object $object
     * @param array  $config
     * @param int    $level
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function normalizeObjectByConfig($object, $config, $level)
    {
        if (!ConfigUtil::isExcludeAll($config)) {
            throw new \RuntimeException(
                sprintf(
                    'The "%s" must be "%s".',
                    ConfigUtil::EXCLUSION_POLICY,
                    ConfigUtil::EXCLUSION_POLICY_ALL
                )
            );
        }
        if (!array_key_exists(ConfigUtil::FIELDS, $config)) {
            throw new \RuntimeException(
                sprintf('The "%s" config does not exist.', ConfigUtil::FIELDS)
            );
        }
        $fields = $config[ConfigUtil::FIELDS];
        if (!is_array($fields)) {
            throw new \RuntimeException(
                sprintf('The "%s" config must be an array.', ConfigUtil::FIELDS)
            );
        }

        $result = [];
        foreach ($fields as $fieldName => $fieldConfig) {
            $value = null;
            if (is_array($fieldConfig)) {
                if (ConfigUtil::isExclude($fieldConfig)) {
                    continue;
                }
                $propertyPath = ConfigUtil::getPropertyPath($fieldConfig, $fieldName);
                if ($this->dataAccessor->tryGetValue($object, $propertyPath, $value) && null !== $value) {
                    $childFields = isset($fieldConfig[ConfigUtil::FIELDS])
                        ? $fieldConfig[ConfigUtil::FIELDS]
                        : null;
                    if (is_string($childFields)) {
                        if ($value instanceof \Traversable) {
                            $childValue = [];
                            foreach ($value as $val) {
                                $childVal = null;
                                $this->dataAccessor->tryGetValue($val, $childFields, $childVal);
                                $childValue[] = $childVal;
                            }
                        } else {
                            $childValue = null;
                            if (!$this->dataAccessor->tryGetValue($value, $childFields, $childValue)) {
                                continue;
                            }
                        }
                        $value = $childValue;
                    } elseif (is_array($childFields)) {
                        $value = $this->normalizeObjectByConfig($value, $fieldConfig, $level + 1);
                    }
                }
            } elseif (!$this->dataAccessor->tryGetValue($object, $fieldName, $value)) {
                continue;
            }
            $result[$fieldName] = $value;
        }

        if (isset($config[ConfigUtil::POST_SERIALIZE])) {
            $result = call_user_func($config[ConfigUtil::POST_SERIALIZE], $result);
        }

        return $result;
    }

    /**
     * @param mixed      $value
     * @param int        $level
     * @param array|null $config
     *
     * @return mixed
     */
    protected function normalizeValue($value, $level, $config = null)
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
            } elseif (!empty($config)) {
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
}
