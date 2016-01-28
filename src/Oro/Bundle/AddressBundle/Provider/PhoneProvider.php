<?php

namespace Oro\Bundle\AddressBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * The aim of this class is to help getting a phone number from an object.
 * The following algorithm is used to get a phone number:
 * 1. check if an object has own phone number
 * 2. loop through registered target entities ordered by priority and check if they have a phone number
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PhoneProvider implements PhoneProviderInterface
{
    const GET_PHONE_METHOD = 'getPhone';

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @var string[]
     */
    protected $targetEntities = [];

    /**
     * @var string[]
     */
    protected $sortedTargetEntities;

    /**
     * @var array
     * key = class name, value = PhoneProviderInterface[]
     */
    protected $phoneProviders = [];

    /**
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(ConfigProvider $extendConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * Registers the entity in supported target entities list
     *
     * @param string  $className
     * @param integer $priority
     */
    public function addTargetEntity($className, $priority = 0)
    {
        $this->targetEntities[$priority][] = $className;
        $this->sortedTargetEntities        = null;
    }

    /**
     * Registers the phone number provider for the given class
     *
     * @param string                 $className
     * @param PhoneProviderInterface $provider
     */
    public function addPhoneProvider($className, PhoneProviderInterface $provider)
    {
        if ($provider instanceof RootPhoneProviderAwareInterface) {
            $provider->setRootProvider($this);
        }
        $this->phoneProviders[$className][] = $provider;
    }

    /**
     * Gets the phone number of the given object
     *
     * @param object $object
     *
     * @return string|null The phone number or null if the object has no phone
     */
    public function getPhoneNumber($object)
    {
        if (!is_object($object)) {
            return null;
        }

        // check if an object has own phone number
        $phoneProviders = $this->getPhoneProviders($object);
        if ($phoneProviders) {
            $phone = null;
            /** @var PhoneProviderInterface $provider */
            foreach ($phoneProviders as $provider) {
                $phone = $provider->getPhoneNumber($object);
                if (!empty($phone)) {
                    break;
                }
            }

            return $phone;
        }

        if (method_exists($object, self::GET_PHONE_METHOD)) {
            $phone = $object->getPhone();
            if (!is_object($phone)) {
                return $phone;
            }
        }

        // check if an object has related object with a phone number
        return $this->getPhoneNumberFromRelatedObject($object);
    }

    /**
     * Gets all available phone numbers of the given object
     *
     * @param object $object
     *
     * @return array of phone number, phone owner
     */
    public function getPhoneNumbers($object)
    {
        if (!is_object($object)) {
            return [];
        }

        // check if an object has own phone number
        $phoneProviders = $this->getPhoneProviders($object);
        if ($phoneProviders) {
            $phones = [];
            /** @var PhoneProviderInterface $provider */
            foreach ($phoneProviders as $provider) {
                $phones = $this->mergePhoneNumbers($phones, $provider->getPhoneNumbers($object));
            }

            return $phones;
        }

        if (method_exists($object, self::GET_PHONE_METHOD)) {
            $phone = $object->{self::GET_PHONE_METHOD}();
            if ($phone && !is_object($phone)) {
                return [[$phone, $object]];
            }
        }

        // check if an object has related object with a phone number
        return $this->getPhoneNumbersFromRelatedObject($object);
    }

    /**
     * @param object $object
     *
     * @return PhoneProviderInterface[]
     */
    protected function getPhoneProviders($object)
    {
        $className = ClassUtils::getClass($object);
        $result = isset($this->phoneProviders[$className]) ? $this->phoneProviders[$className] : [];
        foreach ($this->phoneProviders as $class => $providers) {
            if (is_subclass_of($className, $class)) {
                $result = array_merge($result, $providers);
            }
        }

        return $result;
    }

    /**
     * @param object $object
     *
     * @return string|null
     */
    protected function getPhoneNumberFromRelatedObject($object)
    {
        $applicableRelations = $this->getApplicableRelations($object);
        if (empty($applicableRelations)) {
            return null;
        }

        $targetEntities   = $this->getTargetEntities();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($targetEntities as $className) {
            if (!isset($applicableRelations[$className])) {
                continue;
            }
            foreach ($applicableRelations[$className] as $fieldName) {
                return $this->getPhoneNumber($propertyAccessor->getValue($object, $fieldName));
            }
        }

        return null;
    }

    /**
     * @param object $object
     *
     * @return array of phone number, phone owner
     */
    protected function getPhoneNumbersFromRelatedObject($object)
    {
        $applicableRelations = $this->getApplicableRelations($object, true);
        if (empty($applicableRelations)) {
            return [];
        }

        $result           = [];
        $targetEntities   = $this->getTargetEntities();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($targetEntities as $className) {
            if (!isset($applicableRelations[$className])) {
                continue;
            }
            foreach ($applicableRelations[$className] as $fieldName) {
                $value = $propertyAccessor->getValue($object, $fieldName);
                if (is_array($value) || $value instanceof Collection) {
                    foreach ($value as $val) {
                        $result = $this->mergePhoneNumbers($result, $this->getPhoneNumbers($val));
                    }
                } else {
                    $result = $this->mergePhoneNumbers($result, $this->getPhoneNumbers($value));
                }
            }
        }

        return $result;
    }

    /**
     * @param object $object
     * @param bool   $withMultiValue
     *
     * @return array
     */
    protected function getApplicableRelations($object, $withMultiValue = false)
    {
        $result = [];

        $className = ClassUtils::getClass($object);
        if (!$this->extendConfigProvider->hasConfig($className)) {
            return $result;
        }
        $extendConfig = $this->extendConfigProvider->getConfig($className);
        $relations    = $extendConfig->get('relation');
        if (empty($relations)) {
            return $result;
        }

        $targetEntities = $this->getTargetEntities();
        foreach ($relations as $relation) {
            if (empty($relation['owner'])) {
                continue;
            }
            /** @var FieldConfigId $fieldId */
            $fieldId = $relation['field_id'];

            $isApplicableRelationType =
                $fieldId->getFieldType() === 'manyToOne'
                || ($withMultiValue && $fieldId->getFieldType() === 'manyToMany');
            if (!$isApplicableRelationType) {
                continue;
            }
            $relatedEntityClass = $relation['target_entity'];
            if (!in_array($relatedEntityClass, $targetEntities)) {
                continue;
            }
            if (!isset($result[$relatedEntityClass])) {
                $result[$relatedEntityClass] = [];
            }
            $result[$relatedEntityClass][] = $fieldId->getFieldName();
        }

        return $result;
    }

    /**
     * Sorts the internal list of target entities by priority.
     *
     * @return string[]
     */
    protected function getTargetEntities()
    {
        if (null === $this->sortedTargetEntities) {
            ksort($this->targetEntities);
            $this->sortedTargetEntities = !empty($this->targetEntities)
                ? call_user_func_array('array_merge', $this->targetEntities)
                : [];
        }

        return $this->sortedTargetEntities;
    }

    /**
     * @param array $arr1
     * @param array $arr2
     *
     * @return array
     */
    protected function mergePhoneNumbers(array $arr1, array $arr2)
    {
        foreach ($arr2 as $val) {
            if (!$this->isPhoneNumberExist($arr1, $val)) {
                $arr1[] = $val;
            }
        }

        return $arr1;
    }

    /**
     * @param array $arr
     * @param array $value
     *
     * @return bool
     */
    public function isPhoneNumberExist(array $arr, array $value)
    {
        foreach ($arr as $val) {
            if ($val[0] === $value[0] && $val[1] === $value[1]) {
                return true;
            }
        }

        return false;
    }
}
