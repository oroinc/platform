<?php

namespace Oro\Component\Action\Condition;

use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\ExpressionAssembler;
use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * Wraps and lazily assembles conditions from configuration.
 *
 * This condition acts as a proxy that defers the assembly of the actual condition until evaluation time.
 * It accepts a configuration array and uses an {@see ExpressionAssembler} to construct the appropriate condition
 * instance. This allows for dynamic condition creation based on runtime configuration.
 */
class Configurable extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    public const ALIAS = 'configurable';

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

    #[\Override]
    public function getName()
    {
        return 'configurable';
    }

    public function __construct(ExpressionAssembler $assembler)
    {
        $this->assembler = $assembler;
    }

    #[\Override]
    public function initialize(array $options)
    {
        $this->configuration = $options;

        return $this;
    }

    #[\Override]
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
