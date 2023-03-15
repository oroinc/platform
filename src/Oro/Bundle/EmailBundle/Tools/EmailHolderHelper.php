<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The aim of this class is to help getting an email address from an object.
 * The following algorithm is used to get an email address:
 * 1. check if an object has own email address
 * 2. loop through registered target entities ordered by priority and check if they have an email address
 */
class EmailHolderHelper implements ResetInterface
{
    private const EMAIL_PROPERTY = 'getEmail';

    /** @var ConfigProvider */
    private $extendConfigProvider;

    /** @var array */
    private $targetEntities = [];

    /** @var string[] */
    private $sortedTargetEntities;

    public function __construct(ConfigProvider $extendConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->sortedTargetEntities = null;
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
        }
        $accessor = PropertyAccess::createPropertyAccessor();
        if ($accessor->isReadable($object, self::EMAIL_PROPERTY)) {
            $email = $accessor->getValue($object, self::EMAIL_PROPERTY);
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
    private function getEmailFromRelatedObject($object)
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
    private function getApplicableRelations($object)
    {
        $result = [];

        $className = ClassUtils::getClass($object);
        if (!$this->extendConfigProvider->hasConfig($className)) {
            return $result;
        }
        $extendConfig = $this->extendConfigProvider->getConfig($className);
        $relations = $extendConfig->get('relation');
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
}
