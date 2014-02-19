<?php

namespace Oro\Bundle\WorkflowBundle\Model;

class Attribute
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $entityAcl = array();

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $propertyPath;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Set attribute type
     *
     * @param string $type
     * @return Attribute
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get attribute type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param array $entityAcl
     */
    public function setEntityAcl(array $entityAcl)
    {
        $this->entityAcl = $entityAcl;
    }

    /**
     * @return bool
     */
    public function isEntityUpdateAllowed()
    {
        return !array_key_exists('update', $this->entityAcl) || $this->entityAcl['update'];
    }

    /**
     * @return bool
     */
    public function isEntityDeleteAllowed()
    {
        return !array_key_exists('delete', $this->entityAcl) || $this->entityAcl['delete'];
    }

    /**
     * Set attribute label.
     *
     * @param string $label
     * @return Attribute
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Get attribute label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set attribute name.
     *
     * @param string $name
     * @return Attribute
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get attribute name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set options.
     *
     * @param array $options
     * @return Attribute
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set option by key.
     *
     * @param string $key
     * @param mixed $value
     * @return Attribute
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Get option by key.
     *
     * @param string $key
     * @return null|mixed
     */
    public function getOption($key)
    {
        return $this->hasOption($key) ? $this->options[$key] : null;
    }

    /**
     * Check for option availability by key.
     *
     * @param string $key
     * @return bool
     */
    public function hasOption($key)
    {
        return isset($this->options[$key]);
    }

    /**
     * @return string
     */
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * @param string $propertyPath
     * @return Attribute
     */
    public function setPropertyPath($propertyPath)
    {
        $this->propertyPath = $propertyPath;

        return $this;
    }
}
