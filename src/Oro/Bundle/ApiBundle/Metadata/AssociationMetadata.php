<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Component\ChainProcessor\ToArrayInterface;

class AssociationMetadata extends PropertyMetadata implements ToArrayInterface
{
    /** @var string */
    private $targetClass;

    /** @var string[] */
    private $acceptableTargetClasses = [];

    /** @var string */
    private $associationType;

    /** @var bool */
    private $collection = false;

    /** @var bool */
    private $nullable = false;

    /** @var bool */
    private $collapsed = false;

    /** @var EntityMetadata|null */
    private $targetMetadata;

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        if (null !== $this->targetMetadata) {
            $this->targetMetadata = clone $this->targetMetadata;
        }
    }

    /**
     * Gets a native PHP array representation of the object.
     *
     * @return array [key => value, ...]
     */
    public function toArray()
    {
        $result = array_merge(
            parent::toArray(),
            [
                'nullable'         => $this->nullable,
                'collapsed'        => $this->collapsed,
                'association_type' => $this->associationType,
                'collection'       => $this->collection
            ]
        );
        if ($this->targetClass) {
            $result['target_class'] = $this->targetClass;
        }
        if ($this->acceptableTargetClasses) {
            $result['acceptable_target_classes'] = $this->acceptableTargetClasses;
        }
        if (null !== $this->targetMetadata) {
            $result['target_metadata'] = $this->targetMetadata->toArray();
        }

        return $result;
    }

    /**
     * Gets metadata of the association target.
     *
     * @return EntityMetadata|null
     */
    public function getTargetMetadata()
    {
        return $this->targetMetadata;
    }

    /**
     * Sets metadata of the association target.
     *
     * @param EntityMetadata $targetMetadata
     */
    public function setTargetMetadata(EntityMetadata $targetMetadata)
    {
        $this->targetMetadata = $targetMetadata;
    }

    /**
     * Gets FQCN of the association target.
     *
     * @return string
     */
    public function getTargetClassName()
    {
        return $this->targetClass;
    }

    /**
     * Sets FQCN of the association target.
     *
     * @param string $className
     */
    public function setTargetClassName($className)
    {
        $this->targetClass = $className;
    }

    /**
     * Gets FQCN of acceptable association targets.
     *
     * @return string[]
     */
    public function getAcceptableTargetClassNames()
    {
        return $this->acceptableTargetClasses;
    }

    /**
     * Sets FQCN of acceptable association targets.
     *
     * @param string[] $classNames
     */
    public function setAcceptableTargetClassNames(array $classNames)
    {
        $this->acceptableTargetClasses = $classNames;
    }

    /**
     * Adds new acceptable association target.
     *
     * @param string $className
     */
    public function addAcceptableTargetClassName($className)
    {
        if (!in_array($className, $this->acceptableTargetClasses, true)) {
            $this->acceptableTargetClasses[] = $className;
        }
    }

    /**
     * Removes acceptable association target.
     *
     * @param string $className
     */
    public function removeAcceptableTargetClassName($className)
    {
        $key = array_search($className, $this->acceptableTargetClasses, true);
        if (false !== $key) {
            unset($this->acceptableTargetClasses[$key]);
            $this->acceptableTargetClasses = array_values($this->acceptableTargetClasses);
        }
    }

    /**
     * Gets the type of the association.
     * For example "manyToOne" or "manyToMany".
     * @see Oro\Bundle\EntityExtendBundle\Extend\RelationType
     *
     * @return string
     */
    public function getAssociationType()
    {
        return $this->associationType;
    }

    /**
     * Sets the type of the association.
     * For example "manyToOne" or "manyToMany".
     * @see Oro\Bundle\EntityExtendBundle\Extend\RelationType
     *
     * @param string $associationType
     */
    public function setAssociationType($associationType)
    {
        $this->associationType = $associationType;
    }

    /**
     * Whether the association represents "to-many" or "to-one" relationship.
     *
     * @return bool
     */
    public function isCollection()
    {
        return $this->collection;
    }

    /**
     * Sets a flag indicates whether the association represents "to-many" or "to-one" relationship.
     *
     * @param bool $value TRUE for "to-many" relation, FALSE for "to-one" relationship
     */
    public function setIsCollection($value)
    {
        $this->collection = $value;
    }

    /**
     * Whether a value of the association can be NULL.
     *
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * Sets a flag indicates whether a value of the association can be NULL.
     *
     * @param bool $value
     */
    public function setIsNullable($value)
    {
        $this->nullable = $value;
    }

    /**
     * Indicates whether the association should be collapsed to a scalar.
     *
     * @return bool
     */
    public function isCollapsed()
    {
        return $this->collapsed;
    }

    /**
     * Sets a flag indicates whether the association should be collapsed to a scalar.
     *
     * @param bool $collapsed
     */
    public function setCollapsed($collapsed = true)
    {
        $this->collapsed = $collapsed;
    }
}
