<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Doctrine\Common\Util\ClassUtils;

use Oro\Component\EntitySerializer\DataAccessorInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ItemNormalizer
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
     * @param mixed $item
     *
     * @return mixed
     */
    public function normalizeItem($item)
    {
        if (null !== $item) {
            $item = $this->normalizeValue($item, is_array($item) ? 0 : 1);
        }

        return $item;
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
     * @param mixed $value
     * @param int   $level
     *
     * @return mixed
     */
    protected function normalizeValue($value, $level)
    {
        if (is_array($value)) {
            $nextLevel = $level + 1;
            foreach ($value as &$val) {
                $val = $this->normalizeValue($val, $nextLevel);
            }
        } elseif (is_object($value)) {
            $objectNormalizer = $this->getObjectNormalizer($value);
            if (null !== $objectNormalizer) {
                $value = $objectNormalizer->normalize($value);
            } elseif ($value instanceof \Traversable) {
                $result    = [];
                $nextLevel = $level + 1;
                foreach ($value as $val) {
                    $result[] = $this->normalizeValue($val, $nextLevel);
                }
                $value = $result;
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
                        $value = sprintf(
                            'ERROR: The entity "%s" does not have an identifier.',
                            ClassUtils::getClass($value)
                        );
                    }
                }
            } else {
                if ($level <= static::MAX_NESTING_LEVEL) {
                    $value = $this->normalizePlainObject($value, $level);
                } elseif (method_exists($value, '__toString')) {
                    $value = (string)$value;
                } else {
                    $value = sprintf('ERROR: Instance of "%s" cannot be normalized.', get_class($value));
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
