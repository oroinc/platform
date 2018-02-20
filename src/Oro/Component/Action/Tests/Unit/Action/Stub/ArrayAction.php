<?php

namespace Oro\Component\Action\Tests\Unit\Action\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ExpressionInterface;

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
