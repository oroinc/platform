<?php

namespace Oro\Component\Action\Action;

use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * Wraps and lazily assembles actions from configuration.
 *
 * This action acts as a proxy that defers the assembly of the actual action until execution time.
 * It accepts a configuration array and uses an {@see ActionAssembler} to construct the appropriate action
 * instance. This allows for dynamic action creation based on runtime configuration. The action
 * always allows execution regardless of conditions.
 */
class Configurable implements ActionInterface
{
    public const ALIAS = 'configurable';

    /**
     * @var ActionAssembler
     */
    protected $assembler;

    /**
     * @var ActionInterface
     */
    protected $action;

    /**
     * @var array
     */
    protected $configuration = [];

    public function __construct(ActionAssembler $assembler)
    {
        $this->assembler = $assembler;
    }

    #[\Override]
    public function initialize(array $configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    #[\Override]
    public function execute($context)
    {
        if (!$this->action) {
            $this->action = $this->assembler->assemble($this->configuration);
        }

        $this->action->execute($context);
    }

    /**
     * Configurable action is always allowed
     *
     */
    #[\Override]
    public function setCondition(ExpressionInterface $condition)
    {
    }
}
