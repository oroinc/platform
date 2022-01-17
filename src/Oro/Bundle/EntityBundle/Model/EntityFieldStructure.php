<?php

namespace Oro\Bundle\EntityBundle\Model;

/**
 * Represents detailed information about an entity field.
 */
class EntityFieldStructure
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var string */
    private $label;

    /** @var string */
    private $relationType;

    /** @var string */
    private $relatedEntityName;

    /** @var array */
    private $options = [];

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * @param string $relationType
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;
    }

    /**
     * @return string
     */
    public function getRelatedEntityName()
    {
        return $this->relatedEntityName;
    }

    /**
     * @param string $relatedEntityName
     */
    public function setRelatedEntityName($relatedEntityName)
    {
        $this->relatedEntityName = $relatedEntityName;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function addOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
        if (!$this->hasOption($name)) {
            return null;
        }

        return $this->options[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }

    public function __serialize(): array
    {
        return [
            $this->name,
            $this->type,
            $this->label,
            $this->relationType,
            $this->relatedEntityName,
            $this->options
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->name,
            $this->type,
            $this->label,
            $this->relationType,
            $this->relatedEntityName,
            $this->options
        ] = $serialized;
    }
}
