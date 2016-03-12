<?php

namespace Oro\Bundle\ActionBundle\Model;

class ActionGroupExecutionArgs
{
    /** @var string */
    private $name;

    /** @var ActionData */
    private $arguments = [];

    /**
     * @param $actionGroupName
     * @param ActionData $arguments
     */
    public function __construct($actionGroupName, ActionData $arguments = null)
    {
        $this->name = $actionGroupName;
        $this->arguments = $arguments ?: new ActionData();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function addArgument($name, $value)
    {
        $this->arguments[$name] = $value;

        return $this;
    }

    /**
     * @return ActionData
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
