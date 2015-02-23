<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator\Condition;

use CG\Generator\PhpMethod;

use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface;
use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;

class SimpleContextValueComparisonCondition implements ConditionInterface
{
    /** @var string */
    protected $contextValueName;

    /** @var string */
    protected $condition;

    /** @var mixed */
    protected $value;

    /**
     * @param string $contextValueName Context argument name
     * @param string $condition        Simple comparison condition for if clause such as '===', '!==', '>' etc..
     * @param mixed $value             Value to compare with
     */
    public function __construct($contextValueName, $condition, $value)
    {
        $this->contextValueName = $contextValueName;
        $this->condition        = $condition;
        $this->value            = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(VisitContext $visitContext)
    {
        /** @var PhpMethod[] $methods */
        $methods       = $visitContext->getClass()->getMethods();
        $method        = $methods[LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME];
        $bodyTemplate  = <<<CONTENT
if ($%1\$s->getContext()->has('%2\$s') && $%1\$s->getContext()->get('%2\$s') %3\$s %4\$s) {
    %5\$s
}
CONTENT;

        $method->setBody(
            $visitContext->createWriter()->write(
                sprintf(
                    $bodyTemplate,
                    LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM,
                    $this->contextValueName,
                    $this->condition,
                    var_export($this->value, true),
                    $method->getBody()
                )
            )
        );
    }
}
