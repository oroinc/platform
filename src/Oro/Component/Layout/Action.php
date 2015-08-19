<?php

namespace Oro\Component\Layout;

/**
 * Represents an action
 */
class Action
{
    /** @var string */
    private $name;

    /** @var array */
    private $args;

    /**
     * @param string $name The action name
     * @param array  $args The action arguments
     */
    public function __construct($name, $args)
    {
        $this->name = $name;
        $this->args = $args;
    }

    /**
     * Returns the action name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the action arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->args;
    }

    /**
     * Gets the action argument by its index
     *
     * @param int $index The argument index
     *
     * @return mixed
     */
    public function getArgument($index)
    {
        return $this->args[$index];
    }

    /**
     * Replaces the action argument by its index
     *
     * @param int   $index The argument index
     * @param mixed $value The new value of the argument
     *
     * @return mixed
     */
    public function setArgument($index, $value)
    {
        $this->args[$index] = $value;
    }
}
