<?php

namespace Oro\Bundle\ActionBundle\Model;

class ActionGroupExecutionArgs
{
    /** @var string */
    private $name;

    /** @var array */
    private $arguments = [];

    /**
     * @param $actionGroupName
     * @param array $arguments
     */
    public function __construct($actionGroupName, array $arguments = [])
    {
        $this->name = $actionGroupName;
        $this->arguments = $arguments;
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
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
