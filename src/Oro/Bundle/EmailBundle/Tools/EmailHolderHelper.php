<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * The aim of this class is to help getting an email address from an object.
 * The following algorithm is used to get an email address:
 * 1. check if an object has own email address
 * 2. loop through registered target entities ordered by priority and check if they have an email address
 */
class EmailHolderHelper
{
    const GET_EMAIL_METHOD = 'getEmail';

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
        if (!isset($this->targetEntities[$priority])) {
            $this->targetEntities[$priority] = [];
        }
        $this->targetEntities[$priority][] = $className;
        $this->sortedTargetEntities        = null;
    }

    /**
     * Gets the email address of the given object
     *
     * @param object $object
     *
     * @return string|null The email address or null if the object has no email
     */
    public function getEmail($object)
    {
        if (!is_object($object)) {
            return null;
        }

        // check if an object has own email address
        if ($object instanceof EmailHolderInterface) {
            return $object->getEmail();
        } elseif (method_exists($object, self::GET_EMAIL_METHOD)) {
            $email = $object->getEmail();
            if (!is_object($email)) {
                return $email;
            }
        }

        // check if an object has related object with an email address
        return $this->getEmailFromRelatedObject($object);
    }

    /**
     * @param object $object
     *
     * @return string|null
     */
    protected function getEmailFromRelatedObject($object)
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
                return $this->getEmail($propertyAccessor->getValue($object, $fieldName));
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
