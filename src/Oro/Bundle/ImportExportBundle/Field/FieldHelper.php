<?php

namespace Oro\Bundle\ImportExportBundle\Field;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;

class FieldHelper
{
    /** @var ConfigProviderInterface */
    protected $configProvider;

    /** @var EntityFieldProvider */
    protected $fieldProvider;

    /** @var  FieldTypeHelper */
    protected $fieldTypeHelper;

    /**
     * @param EntityFieldProvider     $fieldProvider
     * @param ConfigProviderInterface $configProvider
     * @param FieldTypeHelper         $fieldTypeHelper
     */
    public function __construct(
        EntityFieldProvider $fieldProvider,
        ConfigProviderInterface $configProvider,
        FieldTypeHelper $fieldTypeHelper
    ) {
        $this->fieldProvider   = $fieldProvider;
        $this->configProvider  = $configProvider;
        $this->fieldTypeHelper = $fieldTypeHelper;
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
        return $this->fieldProvider->getFields(
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
     * @param mixed  $default
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
     * @param string      $className
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
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    public function processRelationAsScalar($className, $fieldName)
    {
        /** @var Config $fieldConfig */
        $fieldConfig = $this->configProvider->getConfig($className, $fieldName);

        return $fieldConfig->is('process_as_scalar');
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
                array('ref-one', 'manyToOne')
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
                array('ref-many', 'oneToMany', 'manyToMany')
            );
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
     * @param mixed  $value
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

    /**
     * @param mixed $data
     * @param string $fieldName
     * @return array
     */
    public function getItemData($data, $fieldName = null)
    {
        if (!is_array($data)) {
            return array();
        }

        if (null === $fieldName) {
            return $data;
        }

        return !empty($data[$fieldName]) ? $data[$fieldName] : array();
    }
}
