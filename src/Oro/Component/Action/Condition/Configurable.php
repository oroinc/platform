<?php

namespace Oro\Component\Action\Condition;

use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\ExpressionAssembler;
use Oro\Component\ConfigExpression\ExpressionInterface;

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
        return $this->getCondition()->evaluate($context, $this->errors) ? true : false;
    }

    /**
     * @return ExpressionInterface
     */
    protected function getCondition()
    {
        if (!$this->condition) {
            $this->condition = $this->assembler->assemble($this->configuration);
        }

        return $this->condition;
    }
}
