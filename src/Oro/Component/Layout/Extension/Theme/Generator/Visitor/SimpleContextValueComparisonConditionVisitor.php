<?php

namespace Oro\Component\Layout\Extension\Theme\Generator\Visitor;

use Oro\Component\Layout\Extension\Theme\Generator\VisitContext;
use Oro\Component\Layout\Extension\Theme\Generator\LayoutUpdateGeneratorInterface;

class SimpleContextValueComparisonConditionVisitor implements VisitorInterface
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
     * @param mixed  $value            Value to compare with
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
    public function startVisit(VisitContext $visitContext)
    {
        $visitContext->getUpdateMethodWriter()
            ->writeln('if (')
            ->indent()
                ->write(sprintf('$%s->getContext()', LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM))
                ->write(sprintf('->getOr(\'%s\')', $this->contextValueName))
                ->write(sprintf(' %s ', $this->condition))
                ->writeln(var_export($this->value, true))
            ->outdent()
            ->writeln(') {')
            ->indent();
    }

    /**
     * {@inheritdoc}
     */
    public function endVisit(VisitContext $visitContext)
    {
        $visitContext->getUpdateMethodWriter()
            ->outdent()
            ->writeln('}');
    }
}
