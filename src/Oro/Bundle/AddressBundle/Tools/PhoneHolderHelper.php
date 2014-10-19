<?php

namespace Oro\Bundle\AddressBundle\Tools;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\AddressBundle\Model\PhoneHolderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

/**
 * The aim of this class is to help getting a phone number from an object.
 * The following algorithm is used to get a phone number:
 * 1. check if an object has own phone number
 * 2. loop through registered target entities ordered by priority and check if they have a phone number
 */
class PhoneHolderHelper
{
    const GET_PHONE_METHOD  = 'getPhone';

    /** @var ConfigProviderInterface */
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
     * @param ConfigProviderInterface $extendConfigProvider
     */
    public function __construct(ConfigProviderInterface $extendConfigProvider)
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
        if (!isset($this->targetEntities[$priority])) {
            $this->targetEntities[$priority] = [];
        }
        $this->targetEntities[$priority][] = $className;
        $this->sortedTargetEntities        = null;
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
        if ($object instanceof PhoneHolderInterface) {
            return $object->getPhoneNumber();
        } elseif (method_exists($object, self::GET_PHONE_METHOD)) {
            $phone = $object->getPhone();
            if (!is_object($phone)) {
                return $phone;
            }
        }

        // check if an object has related object with a phone number
        return $this->getPhoneNumberFromRelatedObject($object);
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
     * @return array
     */
    protected function getApplicableRelations($object)
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
            if ($fieldId->getFieldType() !== 'manyToOne') {
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
}
