<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\ActionBundle\Model\EntityParameterInterface;

/**
 * Represents a workflow variable with type, value, and entity ACL configuration.
 *
 * This class defines a variable used in workflows, including its type, label, property path,
 * and entity ACL settings that control access to the variable data.
 */
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

    /**
     * @param array $entityAcl
     *
     * @return Variable
     */
    #[\Override]
    public function setEntityAcl(array $entityAcl)
    {
        $this->entityAcl = $entityAcl;

        return $this;
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
     *
     * @return Variable
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
     *
     * @return mixed|null
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
     *
     * @return bool
     */
    #[\Override]
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
    #[\Override]
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * @param string $propertyPath
     *
     * @return Variable
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
        return self::INTERNAL_TYPE_VARIABLE;
    }
}
