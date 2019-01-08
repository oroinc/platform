<?php

namespace Oro\Bundle\EntityBundle\Model;

/**
 * Represents detailed information about an entity field.
 */
class EntityFieldStructure implements \Serializable
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
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
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
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
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
     *
     * @return self
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
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
     *
     * @return self
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
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
     *
     * @return self
     */
    public function setRelatedEntityName($relatedEntityName)
    {
        $this->relatedEntityName = $relatedEntityName;

        return $this;
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
     *
     * @return self
     */
    public function addOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
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

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            $this->name,
            $this->type,
            $this->label,
            $this->relationType,
            $this->relatedEntityName,
            $this->options
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->name,
            $this->type,
            $this->label,
            $this->relationType,
            $this->relatedEntityName,
            $this->options
            ) = unserialize($serialized, ['allowed_classes' => false]);
    }
}
