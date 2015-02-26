<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator\Condition;

use CG\Generator\PhpMethod;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;
use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface;

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
if ($%s->getContext()->getOr('%s') %s %s) {
    %s
}
CONTENT;

        $method->setBody(
            $visitContext
                ->createWriter()
                ->write(
                    sprintf(
                        $bodyTemplate,
                        LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM,
                        $this->contextValueName,
                        $this->condition,
                        var_export($this->value, true),
                        $method->getBody()
                    )
                )
                ->getContent()
        );
    }
}
