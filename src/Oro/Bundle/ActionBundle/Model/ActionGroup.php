<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Exception\ForbiddenActionException;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionGroup
{
    /** @var string */
    private $name;

    /** @var array */
    private $actions = [];

    /** @var array */
    private $conditions = [];

    /** @var ActionFactory */
    private $actionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /**
     * @param ActionFactory $actionFactory
     * @param ConditionFactory $conditionFactory
     */
    public function __construct(ActionFactory $actionFactory, ConditionFactory $conditionFactory)
    {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setActions(array $data)
    {
        $this->actions = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setConditions(array $data)
    {
        $this->conditions = $data;

        return $this;
    }

    /**
     * @param ActionData $data
     * @param Collection $errors
     * @throws ForbiddenActionException
     */
    public function execute(ActionData $data, Collection $errors = null)
    {
        if (!$this->isAllowed($data, $errors)) {
            throw new ForbiddenActionException(sprintf('ActionGroup "%s" is not allowed.', $this->getName()));
        }

        $this->executeActions($data);
    }

    /**
     * Check is actionGroup is allowed to execute
     *
     * @param ActionData $data
     * @param Collection|null $errors
     * @return bool
     */
    public function isAllowed(ActionData $data, Collection $errors = null)
    {
        if ($config = $this->getConditions()) {
            $conditions = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $config);
            if ($conditions instanceof ConfigurableCondition) {
                return $conditions->evaluate($data, $errors);
            }
        }

        return true;
    }

    /**
     * @param ActionData $data
     */
    protected function executeActions(ActionData $data)
    {
        if ($config = $this->getActions()) {
            $actions = $this->actionFactory->create(ConfigurableAction::ALIAS, $config);
            if ($actions instanceof ActionInterface) {
                $actions->execute($data);
            }
        }
    }
}
