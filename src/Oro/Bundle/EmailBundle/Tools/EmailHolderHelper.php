<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

class EmailHolderHelper
{
    const GET_EMAIL_METHOD       = 'getEmail';
    const EMAIL_HOLDER_INTERFACE = 'Oro\Bundle\EmailBundle\Model\EmailHolderInterface';

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
     * Checks if the given object can have the email address
     *
     * @param object|string $objectOrClassName
     * @return bool
     */
    public function hasEmail($objectOrClassName)
    {
        if (empty($objectOrClassName)) {
            return false;
        }

        if (is_object($objectOrClassName)) {
            return
                $objectOrClassName instanceof EmailHolderInterface
                || method_exists($objectOrClassName, self::GET_EMAIL_METHOD);
        }
        if (is_string($objectOrClassName)) {
            return
                is_subclass_of($objectOrClassName, self::EMAIL_HOLDER_INTERFACE)
                || method_exists($objectOrClassName, self::GET_EMAIL_METHOD);
        }

        return false;
    }

    /**
     * Gets the email address of the given object
     *
     * @param object $object
     * @return string|null The email address or null if the object has no email
     */
    public function getEmail($object)
    {
        if (!is_object($object)) {
            return null;
        }

        if ($this->hasEmail($object)) {
            return $object->getEmail();
        }

        // check may be an entity has related contact
        // in this case we can get its email
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

        $targetEntities = $this->getTargetEntities();
        foreach ($targetEntities as $className) {
            if (isset($applicableRelations[$className])) {
                foreach ($applicableRelations[$className] as $fieldName) {
                    $propertyAccessor = PropertyAccess::createPropertyAccessor();
                    $relatedObject    = $propertyAccessor->getValue($object, $fieldName);

                    return $this->getEmail($relatedObject);
                }
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
            if (isset($relation['owner']) && $relation['owner']) {
                /** @var FieldConfigId $fieldId */
                $fieldId = $relation['field_id'];
                if ($fieldId->getFieldType() === 'manyToOne') {
                    $relatedEntityClass = $relation['target_entity'];
                    if (in_array($relatedEntityClass, $targetEntities)) {
                        if (!isset($result[$relatedEntityClass])) {
                            $result[$relatedEntityClass] = [];
                        }
                        $result[$relatedEntityClass][] = $fieldId->getFieldName();
                    }
                }
            }
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
