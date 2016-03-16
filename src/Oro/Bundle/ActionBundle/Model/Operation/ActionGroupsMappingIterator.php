<?php

namespace Oro\Bundle\ActionBundle\Model\Operation;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;
use Oro\Bundle\ActionBundle\Model\OperationActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersMapper;

/**
 * Iterator provide mapping for \Oro\Bundle\ActionBundle\Model\OperationActionGroup with ActionData values to
 * \Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs instances
 */
class ActionGroupsMappingIterator extends \ArrayIterator
{
    /** @var ActionData */
    private $data;

    /**
     * @var ParametersMapper
     */
    private $mapper;

    /**
     * @param array|OperationActionGroup[] $actionGroups
     * @param ActionData $data
     */
    public function __construct(array $actionGroups, ActionData $data)
    {
        parent::__construct($actionGroups);
        $this->data = $data;
        $this->mapper = new ParametersMapper();
    }

    /**
     * {@inheritdoc}
     * @return ActionGroupExecutionArgs
     */
    public function current()
    {
        return $this->retrieveArgValues(parent::current());
    }

    /**
     * @param OperationActionGroup $operationActionGroup
     * @return ActionGroupExecutionArgs
     */
    protected function retrieveArgValues(OperationActionGroup $operationActionGroup)
    {
        $executionArgs = new ActionGroupExecutionArgs($operationActionGroup->getName());

        $this->mapper->mapToArgs($executionArgs, $operationActionGroup->getArgumentsMapping(), $this->data);

        return $executionArgs;
    }

    /**
     * @return ActionData
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param ActionData $data
     * @return $this
     */
    public function setData(ActionData $data)
    {
        $this->data = $data;

        return $this;
    }
}
