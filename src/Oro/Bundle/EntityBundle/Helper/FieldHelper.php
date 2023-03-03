<?php

namespace Oro\Bundle\EntityBundle\Helper;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\ReflectionVirtualProperty;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Helper for getting fields and relations for the given entity
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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

    public function __construct(
        EntityFieldProvider $fieldProvider,
        ConfigProvider $configProvider,
        FieldTypeHelper $fieldTypeHelper,
        PropertyAccessorInterface $propertyAccessor,
    ) {
        $this->fieldProvider   = $fieldProvider;
        $this->configProvider  = $configProvider;
        $this->fieldTypeHelper = $fieldTypeHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @see \Oro\Bundle\EntityBundle\Provider\EntityFieldProvider::getEntityFields
     *
     * @param string $entityName
     * @param int $options Bit mask of options, see EntityFieldProvider::OPTION_*
     *
     * @return array
     */
    public function getEntityFields(string $entityName, int $options = EntityFieldProvider::OPTION_TRANSLATE): array
    {
        $args = func_get_args();
        $locale = $this->fieldProvider->getLocale();
        if ($options & EntityFieldProvider::OPTION_TRANSLATE && null !== $locale) {
            $args[] = $locale;
        }

        $cacheKey = implode(':', $args);
        if (!array_key_exists($cacheKey, $this->fieldsCache)) {
            $this->fieldsCache[$cacheKey] = $this->fieldProvider->getEntityFields($entityName, $options);
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
        $args = [$entityName, $fieldName];

        if (null !== $this->fieldProvider->getLocale()) {
            $args[] = $this->fieldProvider->getLocale();
        }

        return implode(':', $args);
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

    public function isSingleDynamicAttribute(array $field): bool
    {
        return 'enum' === ($field['type'] ?? '');
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

    public function getObjectValue($object, $fieldName)
    {
        try {
            return $this->propertyAccessor->getValue($object, $fieldName);
        } catch (\Exception $e) {
            return $this->getObjectValueWithReflection($object, $fieldName, $e);
        }
    }

    public function getObjectValueWithReflection($object, string $fieldName, \Throwable $exception = null)
    {
        $class = ClassUtils::getClass($object);
        while ($class && !EntityPropertyInfo::propertyExists($class, $fieldName) && $class = get_parent_class($class)) {
        }

        if ($exception === null) {
            $exception = new NoSuchPropertyException(
                sprintf(
                    'Property "%s" does not exist in class "%s"',
                    $fieldName,
                    $class
                )
            );
        }

        if ($class && ExtendHelper::isExtendEntity($class)) {
            $reflection = ReflectionVirtualProperty::create($fieldName);
            $reflection->setAccessible(true);

            return $reflection->getValue($object);
        } elseif ($class) {
            $reflection = new \ReflectionProperty($class, $fieldName);
            $reflection->setAccessible(true);

            return $reflection->getValue($object);
        } else {
            throw $exception;
        }
    }

    /**
     * @param object $object
     * @param string $fieldName
     * @param mixed  $value
     * @throws NoSuchPropertyException|\TypeError|\ErrorException|InvalidArgumentException
     */
    public function setObjectValue($object, $fieldName, $value)
    {
        $propertyPath = new PropertyPath($fieldName);

        try {
            $this->propertyAccessor->setValue($object, $propertyPath, $value);
        } catch (NoSuchPropertyException|\TypeError|\ErrorException|InvalidArgumentException $e) {
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
     * @param NoSuchPropertyException|\TypeError|\ErrorException|InvalidArgumentException $exception
     * @throws NoSuchPropertyException|\TypeError|\ErrorException|InvalidArgumentException
     */
    protected function setObjectValueWithReflection($object, $fieldName, $value, $exception = null)
    {
        $class = ClassUtils::getClass($object);
        while ($class && !property_exists($class, $fieldName) && $class = get_parent_class($class)) {
        }

        if ($exception === null) {
            $exception = new NoSuchPropertyException(sprintf(
                'Property "%s" does not exist in class "%s"',
                $fieldName,
                $class
            ));
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

            $fields = $this->getEntityFields(
                $entityName,
                EntityFieldProvider::OPTION_WITH_RELATIONS | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
            );
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
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->fieldProvider->setLocale($locale);
    }

    /**
     * Exclude fields marked as "excluded" and skipped not identity fields
     *
     * @param string           $entityName
     * @param string           $fieldName
     * @param array|mixed|null $itemData
     *
     * @return bool
     */
    public function isFieldExcluded($entityName, $fieldName, $itemData = null)
    {
        if ($this->getConfigValue($entityName, $fieldName, 'excluded', false)) {
            return true;
        }

        $isIdentity = $this->isIdentityField($entityName, $fieldName, $itemData);
        $isSkipped  = $itemData !== null && !array_key_exists($fieldName, $itemData);

        return $isSkipped && !$isIdentity;
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param array|null $itemData
     *
     * @return bool
     */
    protected function isIdentityField($entityName, $fieldName, $itemData = null)
    {
        $isIdentity = $this->getConfigValue($entityName, $fieldName, 'identity', false);
        if (false === $isIdentity) {
            return $isIdentity;
        }

        $isInputDataContainsField = is_array($itemData) && array_key_exists($fieldName, $itemData);

        return $this->isRequiredIdentityField($entityName, $fieldName) || $isInputDataContainsField;
    }
}
