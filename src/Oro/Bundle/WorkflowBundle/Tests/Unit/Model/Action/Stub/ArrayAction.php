<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action\Stub;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\ConfigExpression\ExpressionInterface;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\Action\ActionInterface;

class ArrayAction extends ArrayCollection implements ActionInterface
{
    /**
     * @var ExpressionInterface
     */
    protected $condition;

    /**
     * Do nothing
     *
     * @param mixed $context
     */
    public function execute($context)
    {
    }

    /**
     * @param array $options
     * @return ActionInterface
     * @throws InvalidParameterException
     */
    public function initialize(array $options)
    {
        $this->set('parameters', $options);
        return $this;
    }

    /**
     * @param ExpressionInterface $condition
     * @return mixed
     */
    public function setCondition(ExpressionInterface $condition)
    {
        $this->condition = $condition;
    }

    /**
     * @return ExpressionInterface
     */
    public function getCondition()
    {
        return $this->condition;
    }
}
