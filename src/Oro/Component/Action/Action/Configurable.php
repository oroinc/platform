<?php

namespace Oro\Component\Action\Action;

use Oro\Component\ConfigExpression\ExpressionInterface;

class Configurable implements ActionInterface
{
    const ALIAS = 'configurable';

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
