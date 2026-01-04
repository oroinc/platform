<?php

namespace Oro\Bundle\ActionBundle\Model;

/**
 * Represents action attribute.
 */
class Attribute implements EntityParameterInterface
{
    public const INTERNAL_TYPE_ATTRIBUTE = 'attribute';

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
    protected $entityAcl = [];

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
    protected $options = [];

    /**
     * @var mixed
     */
    protected $default;

    /**
     * Set attribute type
     *
     * @param string $type
     * @return Attribute
     */
    #[\Override]
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
    #[\Override]
    public function getType()
    {
        return $this->type;
    }

    #[\Override]
    public function setEntityAcl(array $entityAcl)
    {
        $this->entityAcl = $entityAcl;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function isEntityUpdateAllowed()
    {
        return !array_key_exists('update', $this->entityAcl) || $this->entityAcl['update'];
    }

    /**
     * @return bool
     */
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
    public function hasOption($key)
    {
        return isset($this->options[$key]);
    }

    /**
     * @return string
     */
    #[\Override]
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * @param string $propertyPath
     * @return Attribute
     */
    #[\Override]
    public function setPropertyPath($propertyPath)
    {
        $this->propertyPath = $propertyPath;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getInternalType()
    {
        return self::INTERNAL_TYPE_ATTRIBUTE;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function setDefault(mixed $default): Attribute
    {
        $this->default = $default;

        return $this;
    }
}
