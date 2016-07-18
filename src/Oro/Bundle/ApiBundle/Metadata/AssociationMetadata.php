<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Component\ChainProcessor\ToArrayInterface;

class AssociationMetadata implements ToArrayInterface
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $dataType;

    /** @var string */
    protected $targetClass;

    /** @var string[] */
    protected $acceptableTargetClasses = [];

    /** @var bool */
    protected $collection = false;

    /** @var bool */
    protected $nullable = false;

    /** @var EntityMetadata|null */
    private $targetMetadata;

    /**
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

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
        $result = ['name' => $this->name];
        if ($this->dataType) {
            $result['data_type'] = $this->dataType;
        }
        if ($this->targetClass) {
            $result['target_class'] = $this->targetClass;
        }
        if ($this->acceptableTargetClasses) {
            $result['acceptable_target_classes'] = $this->acceptableTargetClasses;
        }
        if ($this->collection) {
            $result['collection'] = $this->collection;
        }
        if ($this->nullable) {
            $result['nullable'] = $this->nullable;
        }
        if (null !== $this->targetMetadata) {
            $result['target_metadata'] = $this->targetMetadata->toArray();
        }

        return $result;
    }

    /**
     * Gets the name of the association.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the association.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the data-type of the association identifier field.
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Sets the data-type of the association identifier field.
     *
     * @param string $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
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
}
