<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension\Generator;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;

class ConfigExpressionConditionVisitor implements VisitorInterface
{
    /** @var ParsedExpression */
    protected $expression;

    /** @var  ExpressionLanguage */
    protected $expressionLanguage;

    /**
     * @param ParsedExpression $expression Compiled expression
     * @param ExpressionLanguage $expressionLanguage
     */
    public function __construct(ParsedExpression $expression, ExpressionLanguage $expressionLanguage)
    {
        $this->expression = $expression;
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisit(VisitContext $visitContext)
    {
        $visitContext->getUpdateMethodWriter()
            ->writeln(
                sprintf(
                    '$context = $%s->getContext();',
                    LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM
                )
            )
            ->writeln(sprintf('if (%s) {', $this->expressionLanguage->compile($this->expression)))
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
