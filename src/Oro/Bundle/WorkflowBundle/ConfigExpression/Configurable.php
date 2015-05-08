<?php

namespace Oro\Bundle\WorkflowBundle\ConfigExpression;

use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\ConfigExpression\ExpressionAssembler;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;

class Configurable extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const ALIAS = 'configurable';

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var ExpressionInterface
     */
    protected $condition;

    /**
     * @var ExpressionAssembler
     */
    protected $assembler;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'configurable';
    }


    public function __construct(ExpressionAssembler $assembler)
    {
        $this->assembler = $assembler;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $params = [$this->configuration];
        if ($this->condition !== null) {
            $params[] = $this->condition->toArray();
        }

        return $this->convertToArray($params);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        $params = [$this->configuration];
        if ($this->condition !== null) {
            $params[] = $this->condition->toArray();
        }

        return $this->convertToPhpCode($params, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->configuration = $options;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isConditionAllowed($context)
    {
        if (!$this->condition) {
            $this->condition = $this->assembler->assemble($this->configuration);
        }

        return $this->condition->evaluate($context, $this->errors) ? true : false;
    }
}
