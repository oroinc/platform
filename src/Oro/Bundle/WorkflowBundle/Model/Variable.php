<?php

namespace Oro\Bundle\WorkflowBundle\Model;

class Variable
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
     * @var string
     */
    protected $label;

    /**
     * Set variable type
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
     * Get variable type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set variable label.
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
}
