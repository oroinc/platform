<?php

namespace Oro\Bundle\ApiBundle\Metadata;

class AssociationMetadata extends PropertyMetadata
{
    /** FQCN of an association target */
    const TARGET_CLASS_NAME = 'targetClass';

    /** FQCN of acceptable association targets */
    const ACCEPTABLE_TARGET_CLASS_NAMES = 'acceptableTargetClasses';

    /** a flag indicates if an association represents "to-many" or "to-one" relation */
    const COLLECTION = 'collection';

    /** @var EntityMetadata|null */
    private $targetMetadata;

    /**
     * Make a deep copy of object.
     */
    public function __clone()
    {
        parent::__clone();
        if (null !== $this->targetMetadata) {
            $this->targetMetadata = clone $this->targetMetadata;
        }
    }

    /**
     * Gets metadata of an association target.
     *
     * @return EntityMetadata|null
     */
    public function getTargetMetadata()
    {
        return $this->targetMetadata;
    }

    /**
     * Sets metadata of an association target.
     *
     * @param EntityMetadata $targetMetadata
     */
    public function setTargetMetadata(EntityMetadata $targetMetadata)
    {
        $this->targetMetadata = $targetMetadata;
    }

    /**
     * Gets FQCN of an association target.
     *
     * @return string
     */
    public function getTargetClassName()
    {
        return $this->get(self::TARGET_CLASS_NAME);
    }

    /**
     * Sets FQCN of an association target.
     *
     * @param string $className
     */
    public function setTargetClassName($className)
    {
        $this->set(self::TARGET_CLASS_NAME, $className);
    }

    /**
     * Gets FQCN of acceptable association targets.
     *
     * @return string[]
     */
    public function getAcceptableTargetClassNames()
    {
        $classNames = $this->get(self::ACCEPTABLE_TARGET_CLASS_NAMES);

        return null !== $classNames
            ? $classNames
            : [];
    }

    /**
     * Sets FQCN of acceptable association targets.
     *
     * @param string[] $classNames
     */
    public function setAcceptableTargetClassNames(array $classNames)
    {
        $this->set(self::ACCEPTABLE_TARGET_CLASS_NAMES, $classNames);
    }

    /**
     * Adds new acceptable association target.
     *
     * @param string $className
     */
    public function addAcceptableTargetClassName($className)
    {
        $classNames = $this->getAcceptableTargetClassNames();
        if (!in_array($className, $classNames, true)) {
            $classNames[] = $className;
        }
        $this->set(self::ACCEPTABLE_TARGET_CLASS_NAMES, $classNames);
    }

    /**
     * Removes acceptable association target.
     *
     * @param string $className
     */
    public function removeAcceptableTargetClassName($className)
    {
        $classNames = $this->getAcceptableTargetClassNames();
        $key = array_search($className, $classNames, true);
        if (false !== $key) {
            unset($classNames[$key]);
            $this->set(self::ACCEPTABLE_TARGET_CLASS_NAMES, array_values($classNames));
        }
    }

    /**
     * Whether an association represents "to-many" or "to-one" relation.
     *
     * @return bool
     */
    public function isCollection()
    {
        return (bool)$this->get(self::COLLECTION);
    }

    /**
     * Sets a flag indicates whether an association represents "to-many" or "to-one" relation.
     *
     * @param bool $value TRUE for "to-many" relation, FALSE for "to-one" relation
     */
    public function setIsCollection($value)
    {
        $this->set(self::COLLECTION, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = parent::toArray();

        if (null !== $this->targetMetadata) {
            $result['targetMetadata'] = $this->targetMetadata->toArray();
        }

        return $result;
    }
}
