<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\ActionBundle\Model\EntityParameterInterface;

class Variable implements EntityParameterInterface
{
    const INTERNAL_TYPE_VARIABLE = 'variable';

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
    protected $propertyPath;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Set attribute type
     *
     * @param string $type
     *
     * @return Variable
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
     *
     * @return Variable
     */
    public function setEntityAcl(array $entityAcl)
    {
        $this->entityAcl = $entityAcl;

        return $this;
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
     * @param mixed $value
     *
     * @return Variable
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set attribute label.
     *
     * @param string $label
     *
     * @return Variable
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get variable label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set variable name.
     *
     * @param string $name
     *
     * @return Variable
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get variable name.
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
     *
     * @return Variable
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
     *
     * @return Variable
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
     *
     * @return mixed|null
     */
    public function getOption($key)
    {
        return $this->hasOption($key) ? $this->options[$key] : null;
    }

    /**
     * Check for option availability by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasOption($key)
    {
        return isset($this->options[$key]);
    }

    /**
     * @return array|mixed
     */
    public function getFormOptions()
    {
        if (!$this->hasOption('form_options')) {
            return [];
        }

        return $this->options['form_options'];
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
     *
     * @return Variable
     */
    public function setPropertyPath($propertyPath)
    {
        $this->propertyPath = $propertyPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getInternalType()
    {
        return self::INTERNAL_TYPE_VARIABLE;
    }
}
