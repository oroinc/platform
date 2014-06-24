<?php

namespace Oro\Bundle\ImportExportBundle\Field;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

class FieldHelper
{
    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var EntityFieldProvider
     */
    protected $fieldProvider;

    /**
     * @param EntityFieldProvider $fieldProvider
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(EntityFieldProvider $fieldProvider, ConfigProviderInterface $configProvider)
    {
        $this->fieldProvider = $fieldProvider;
        $this->configProvider = $configProvider;
    }

    /**
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
        return $this
            ->fieldProvider->getFields(
                $entityName,
                $withRelations,
                $withVirtualFields,
                $withEntityDetails,
                $withUnidirectional,
                $applyExclusions,
                $translate
            );
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param string $parameter
     * @param mixed $default
     * @return mixed|null
     */
    public function getConfigValue($entityName, $fieldName, $parameter, $default = null)
    {
        if (!$this->configProvider->hasConfig($entityName, $fieldName)) {
            return $default;
        }

        $fieldConfig = $this->configProvider->getConfig($entityName, $fieldName);
        if (!$fieldConfig->has($parameter)) {
            return $default;
        }

        return $fieldConfig->get($parameter);
    }

    /**
     * @param string $className
     * @param null|string $fieldName
     * @return bool
     */
    public function hasConfig($className, $fieldName = null)
    {
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
     * @param array $field
     * @return bool
     */
    public function isSingleRelation(array $field)
    {
        return $this->isRelation($field)
            && in_array($field['relation_type'], array('ref-one', 'oneToOne', 'manyToOne'));
    }

    /**
     * @param array $field
     * @return bool
     */
    public function isMultipleRelation(array $field)
    {
        return $this->isRelation($field)
            && in_array($field['relation_type'], array('ref-many', 'oneToMany', 'manyToMany'));
    }

    /**
     * @param array $field
     * @return bool
     */
    public function isDateTimeField(array $field)
    {
        return !empty($field['type']) && in_array($field['type'], array('datetime', 'date', 'time'));
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
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            return $propertyAccessor->getValue($object, $fieldName);
        } catch (\Exception $e) {
            $class = ClassUtils::getClass($object);
            if (property_exists($class, $fieldName)) {
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
     * @param mixed $value
     * @throws \Exception
     */
    public function setObjectValue($object, $fieldName, $value)
    {
        try {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $propertyAccessor->setValue($object, $fieldName, $value);
        } catch (\Exception $e) {
            $class = ClassUtils::getClass($object);
            if (property_exists($class, $fieldName)) {
                $reflection = new \ReflectionProperty($class, $fieldName);
                $reflection->setAccessible(true);
                $reflection->setValue($object, $value);
            } else {
                throw $e;
            }
        }
    }
}
