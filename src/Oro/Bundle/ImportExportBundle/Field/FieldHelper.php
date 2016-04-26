<?php

namespace Oro\Bundle\ImportExportBundle\Field;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;

class FieldHelper
{
    const HAS_CONFIG = 'has_config';

    const IDENTITY_ONLY_WHEN_NOT_EMPTY = -1;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var EntityFieldProvider */
    protected $fieldProvider;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var array */
    protected $fieldsCache = [];

    /** @var array */
    protected $relationsCache = [];

    /** @var array */
    protected $fieldsConfigCache = [];

    /** @var array */
    protected $identityFieldsCache = [];

    /**
     * @param EntityFieldProvider $fieldProvider
     * @param ConfigProvider      $configProvider
     * @param FieldTypeHelper     $fieldTypeHelper
     */
    public function __construct(
        EntityFieldProvider $fieldProvider,
        ConfigProvider $configProvider,
        FieldTypeHelper $fieldTypeHelper
    ) {
        $this->fieldProvider   = $fieldProvider;
        $this->configProvider  = $configProvider;
        $this->fieldTypeHelper = $fieldTypeHelper;
    }

    /**
     * @see \Oro\Bundle\EntityBundle\Provider\EntityFieldProvider::getFields
     *
     * @param string $entityName
     * @param bool   $withRelations
     * @param bool   $withVirtualFields
     * @param bool   $withEntityDetails
     * @param bool   $withUnidirectional
     * @param bool   $applyExclusions
     * @param bool   $translate
     * @return array
     */
    public function getFields(
        $entityName,
        $withRelations = false,
        $withVirtualFields = false,
        $withEntityDetails = false,
        $withUnidirectional = false,
        $applyExclusions = false,
        $translate = true
    ) {
        $args = func_get_args();
        $cacheKey = implode(':', $args);
        if (!array_key_exists($cacheKey, $this->fieldsCache)) {
            $this->fieldsCache[$cacheKey] = $this->fieldProvider->getFields(
                $entityName,
                $withRelations,
                $withVirtualFields,
                $withEntityDetails,
                $withUnidirectional,
                $applyExclusions,
                $translate
            );
        }

        return $this->fieldsCache[$cacheKey];
    }

    /**
     * @see \Oro\Bundle\EntityBundle\Provider\EntityFieldProvider::getRelations
     *
     * @param string $entityName
     * @param bool $withEntityDetails
     * @param bool $applyExclusions
     * @param bool $translate
     * @return array
     */
    public function getRelations(
        $entityName,
        $withEntityDetails = false,
        $applyExclusions = true,
        $translate = true
    ) {
        $args = func_get_args();
        $cacheKey = implode(':', $args);
        if (!array_key_exists($cacheKey, $this->relationsCache)) {
            $this->relationsCache[$cacheKey] = $this->fieldProvider->getRelations(
                $entityName,
                $withEntityDetails,
                $applyExclusions,
                $translate
            );
        }

        return $this->relationsCache[$cacheKey];
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param string $parameter
     * @param mixed  $default
     * @return mixed|null
     */
    public function getConfigValue($entityName, $fieldName, $parameter, $default = null)
    {
        $key = $this->getCacheKey($entityName, $fieldName);

        if (array_key_exists($key, $this->fieldsConfigCache)
            && array_key_exists($parameter, $this->fieldsConfigCache[$key])
        ) {
            return $this->fieldsConfigCache[$key][$parameter];
        }

        if (!$this->configProvider->hasConfig($entityName, $fieldName)) {
            $this->fieldsConfigCache[$key][self::HAS_CONFIG] = false;
            $this->fieldsConfigCache[$key][$parameter] = $default;

            return $this->fieldsConfigCache[$key][$parameter];
        }

        $this->fieldsConfigCache[$key][self::HAS_CONFIG] = true;
        $this->fieldsConfigCache[$key][$parameter] = $this->configProvider->getConfig($entityName, $fieldName)
            ->get($parameter, false, $default);

        return $this->fieldsConfigCache[$key][$parameter];
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return string
     */
    protected function getCacheKey($entityName, $fieldName)
    {
        return $entityName . ':' . $fieldName;
    }

    /**
     * @param string      $className
     * @param null|string $fieldName
     * @return bool
     */
    public function hasConfig($className, $fieldName = null)
    {
        $key = $this->getCacheKey($className, $fieldName);
        if (array_key_exists($key, $this->fieldsConfigCache)) {
            return !empty($this->fieldsConfigCache[$key][self::HAS_CONFIG]);
        }

        return $this->configProvider->hasConfig($className, $fieldName);
    }

    /**
     * @param array $field
     * @return bool
     */
    public function isRelation(array $field)
    {
        return !empty($field['relation_type']) && !empty($field['related_entity_name']);
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    public function processRelationAsScalar($className, $fieldName)
    {
        return (bool)$this->getConfigValue($className, $fieldName, 'process_as_scalar', false);
    }

    /**
     * @param array $field
     * @return bool
     */
    public function isSingleRelation(array $field)
    {
        return
            $this->isRelation($field)
            && in_array(
                $this->fieldTypeHelper->getUnderlyingType($field['relation_type']),
                ['ref-one', 'manyToOne']
            );
    }

    /**
     * @param array $field
     * @return bool
     */
    public function isMultipleRelation(array $field)
    {
        return
            $this->isRelation($field)
            && in_array(
                $this->fieldTypeHelper->getUnderlyingType($field['relation_type']),
                ['ref-many', 'oneToMany', 'manyToMany']
            );
    }

    /**
     * @param array $field
     * @return bool
     */
    public function isDateTimeField(array $field)
    {
        return !empty($field['type']) && in_array($field['type'], ['datetime', 'date', 'time']);
    }

    /**
     * @param object $object
     * @param string $fieldName
     * @return mixed
     * @throws \Exception
     */
    public function getObjectValue($object, $fieldName)
    {
        try {
            return $this->getPropertyAccessor()->getValue($object, $fieldName);
        } catch (\Exception $e) {
            $class = ClassUtils::getClass($object);
            while (!property_exists($class, $fieldName) && $class = get_parent_class($class)) {
            }

            if ($class) {
                $reflection = new \ReflectionProperty($class, $fieldName);
                $reflection->setAccessible(true);
                return $reflection->getValue($object);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param object $object
     * @param string $fieldName
     * @param mixed  $value
     * @throws NoSuchPropertyException|\TypeError|\ErrorException
     */
    public function setObjectValue($object, $fieldName, $value)
    {
        try {
            $this->getPropertyAccessor()->setValue($object, $fieldName, $value);
        } catch (NoSuchPropertyException $e) {
            $this->setObjectValueWithReflection($object, $fieldName, $value, $e);
        } catch (\TypeError $e) {
            $this->setObjectValueWithReflection($object, $fieldName, $value, $e);
        } catch (\ErrorException $e) {
            $this->setObjectValueWithReflection($object, $fieldName, $value, $e);
        }
    }

    /**
     * If Property accessor have type_error
     * try added value by ReflectionProperty
     *
     * @param object $object
     * @param string $fieldName
     * @param mixed  $value
     * @param NoSuchPropertyException|\TypeError|\ErrorException $exception
     * @throws NoSuchPropertyException|\TypeError|\ErrorException
     */
    protected function setObjectValueWithReflection($object, $fieldName, $value, $exception)
    {
        $class = ClassUtils::getClass($object);
        while (!property_exists($class, $fieldName) && $class = get_parent_class($class)) {
        }

        if ($class) {
            $reflection = new \ReflectionProperty($class, $fieldName);
            $reflection->setAccessible(true);
            $reflection->setValue($object, $value);
        } else {
            throw $exception;
        }
    }

    /**
     * @param mixed $data
     * @param string $fieldName
     * @return array
     */
    public function getItemData($data, $fieldName = null)
    {
        if (!is_array($data)) {
            return [];
        }

        if (null === $fieldName) {
            return $data;
        }

        return !empty($data[$fieldName]) ? $data[$fieldName] : [];
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getIdentityValues($entity)
    {
        $entityName = ClassUtils::getClass($entity);
        $identityFieldNames = $this->getIdentityFieldNames($entityName);

        return $this->getFieldsValues($entity, $identityFieldNames);
    }

    /**
     * Checks if a field should be used as an identity even if it has empty value
     *
     * @param string $entityName
     * @param string $fieldName
     *
     * @return bool
     */
    public function isRequiredIdentityField($entityName, $fieldName)
    {
        $value = $this->getConfigValue($entityName, $fieldName, 'identity', false);

        return $value && self::IDENTITY_ONLY_WHEN_NOT_EMPTY !== $value;
    }

    /**
     * @param string $entityName
     * @return string[]
     */
    public function getIdentityFieldNames($entityName)
    {
        if (!array_key_exists($entityName, $this->identityFieldsCache)) {
            $this->identityFieldsCache[$entityName] = [];

            $fields = $this->getFields($entityName, true);
            foreach ($fields as $field) {
                $fieldName = $field['name'];
                if (!$this->getConfigValue($entityName, $fieldName, 'excluded', false)
                    && $this->getConfigValue($entityName, $fieldName, 'identity', false)
                ) {
                    $this->identityFieldsCache[$entityName][] = $fieldName;
                }
            }
        }

        return $this->identityFieldsCache[$entityName];
    }

    /**
     * @param object $entity
     * @param array $fieldNames
     * @return array
     */
    public function getFieldsValues($entity, $fieldNames)
    {
        $values = [];
        foreach ($fieldNames as $fieldName) {
            $values[$fieldName] = $this->getObjectValue($entity, $fieldName);
        }

        return $values;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
