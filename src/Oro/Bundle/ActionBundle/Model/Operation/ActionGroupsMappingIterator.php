<?php

namespace Oro\Bundle\ActionBundle\Model\Operation;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Model\ContextAccessor;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;
use Oro\Bundle\ActionBundle\Model\OperationActionGroup;

/**
 * Iterator provide mapping for \Oro\Bundle\ActionBundle\Model\OperationActionGroup with ActionData values to
 * \Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs instances
 */
class ActionGroupsMappingIterator extends \ArrayIterator
{
    /** @var ActionData */
    private $data;

    /** @var ContextAccessor */
    private $accessor;

    /**
     * @param array|OperationActionGroup[] $actionGroups
     * @param ActionData $data
     * @param ContextAccessor|null $accessor
     */
    public function __construct(array $actionGroups, ActionData $data, ContextAccessor $accessor = null)
    {
        parent::__construct($actionGroups);
        $this->data = $data;
        $this->accessor = $accessor ?: new ContextAccessor();
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

        foreach ($operationActionGroup->getParametersMapping() as $parameterName => $value) {
            if ($value instanceof PropertyPathInterface) {
                $value = $this->accessor->getValue($this->data, $value);
            } elseif (is_array($value)) {
                array_walk_recursive(
                    $value,
                    function (&$element) {
                        if ($element instanceof PropertyPathInterface) {
                            $element = $this->accessor->getValue($this->data, $element);
                        }
                    }
                );
            }

            $executionArgs->addParameter($parameterName, $value);
        }

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
