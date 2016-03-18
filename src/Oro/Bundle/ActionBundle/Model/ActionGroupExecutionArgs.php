<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

class ActionGroupExecutionArgs
{
    /** @var string */
    private $name;

    /** @var ActionData */
    private $arguments = [];

    /**
     * @param string $actionGroupName
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
    public function getActionGroupName()
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
     * Creates new instance of action data with default root element as arguments object \stdClass
     * @return ActionData
     */
    public function getActionData()
    {
        return new ActionData(['data' => (object)$this->arguments]);
    }

    /**
     * @param ActionGroupRegistry $registry
     * @param Collection|null $errors
     * @return mixed
     */
    public function execute(ActionGroupRegistry $registry, Collection $errors = null)
    {
        return $registry->get($this->getActionGroupName())->execute($this->getActionData(), $errors);
    }
}
