<?php

namespace Oro\Bundle\AddressBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The aim of this class is to help getting a phone number from an object.
 * The following algorithm is used to get a phone number:
 * 1. check if an object has own phone number
 * 2. loop through registered target entities ordered by priority and check if they have a phone number
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PhoneProvider implements PhoneProviderInterface, ResetInterface
{
    private const PHONE_PROPERTY = 'getPhone';

    /** @var array [class name => [provider id, ...], ...] */
    private $phoneProviderMap;

    /** @var ContainerInterface */
    private $phoneProviderContainer;

    /** @var ConfigProvider */
    private $extendConfigProvider;

    /** @var array */
    private $targetEntities = [];

    /** @var string[] */
    private $sortedTargetEntities;

    /** @var array [class name => [provider, ...], ...] */
    private $phoneProviders = [];

    public function __construct(
        array $phoneProviderMap,
        ContainerInterface $phoneProviderContainer,
        ConfigProvider $extendConfigProvider
    ) {
        $this->phoneProviderMap = $phoneProviderMap;
        $this->phoneProviderContainer = $phoneProviderContainer;
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->sortedTargetEntities = null;
        $this->phoneProviders = [];
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
        $this->sortedTargetEntities = null;
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

        $accessor = PropertyAccess::createPropertyAccessor();
        if ($accessor->isReadable($object, self::PHONE_PROPERTY)) {
            $phone = $accessor->getValue($object, self::PHONE_PROPERTY);
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

        $accessor = PropertyAccess::createPropertyAccessor();
        if ($accessor->isReadable($object, self::PHONE_PROPERTY)) {
            $phone = $accessor->getValue($object, self::PHONE_PROPERTY);
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
    private function getPhoneProviders($object)
    {
        $className = ClassUtils::getClass($object);
        if (isset($this->phoneProviders[$className])) {
            return $this->phoneProviders[$className];
        }

        $providerIds = [];
        if (isset($this->phoneProviderMap[$className])) {
            $providerIds[] = $this->phoneProviderMap[$className];
        }
        foreach ($this->phoneProviderMap as $class => $ids) {
            if (is_subclass_of($className, $class)) {
                $providerIds[] = $ids;
            }
        }
        if ($providerIds) {
            $providerIds = array_merge(...$providerIds);
        }

        $providers = [];
        foreach ($providerIds as $id) {
            $provider = $this->phoneProviderContainer->get($id);
            if ($provider instanceof RootPhoneProviderAwareInterface) {
                $provider->setRootProvider($this);
            }
            $providers[] = $provider;
        }
        $this->phoneProviders[$className] = $providers;

        return $providers;
    }

    /**
     * @param object $object
     *
     * @return string|null
     */
    private function getPhoneNumberFromRelatedObject($object)
    {
        $applicableRelations = $this->getApplicableRelations($object);
        if (empty($applicableRelations)) {
            return null;
        }

        $targetEntities = $this->getTargetEntities();
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
    private function getPhoneNumbersFromRelatedObject($object)
    {
        $applicableRelations = $this->getApplicableRelations($object, true);
        if (empty($applicableRelations)) {
            return [];
        }

        $result = [];
        $targetEntities = $this->getTargetEntities();
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getApplicableRelations($object, $withMultiValue = false)
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
    private function getTargetEntities()
    {
        if (null === $this->sortedTargetEntities) {
            ksort($this->targetEntities);
            $this->sortedTargetEntities = $this->targetEntities
                ? array_merge(...array_values($this->targetEntities))
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
    private function mergePhoneNumbers(array $arr1, array $arr2)
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
    private function isPhoneNumberExist(array $arr, array $value)
    {
        foreach ($arr as $val) {
            if ($val[0] === $value[0] && $val[1] === $value[1]) {
                return true;
            }
        }

        return false;
    }
}
