<?php

namespace Oro\Bundle\ActionBundle\Model;

class ActionGroupExecutionArgs
{
    /** @var string */
    private $name;

    /** @var ActionData */
    private $parameters = [];

    /**
     * @param string $actionGroupName
     * @param array $parameters
     */
    public function __construct($actionGroupName, array $parameters = [])
    {
        $this->name = $actionGroupName;
        $this->parameters = $parameters;
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
    public function addParameter($name, $value)
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    /**
     * Creates new instance of action data with default root element as parameters object \stdClass
     * @return ActionData
     */
    public function getActionData()
    {
        return new ActionData(['data' => (object)$this->parameters]);
    }
}
