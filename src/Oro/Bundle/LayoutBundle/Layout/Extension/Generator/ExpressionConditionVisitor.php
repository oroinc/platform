<?php
declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Layout\Extension\Generator;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\IsApplicableLayoutUpdateInterface;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

/**
 * Expression condition visitor used by \Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ExpressionGeneratorExtension
 */
class ExpressionConditionVisitor implements VisitorInterface
{
    protected ParsedExpression $expression;

    protected ExpressionLanguage $expressionLanguage;

    public function __construct(ParsedExpression $expression, ExpressionLanguage $expressionLanguage)
    {
        $this->expression = $expression;
        $this->expressionLanguage = $expressionLanguage;
    }

    public function startVisit(VisitContext $visitContext): void
    {
        $class  = $visitContext->getClass();
        if (!\in_array(IsApplicableLayoutUpdateInterface::class, $class->getImplements())) {
            $class->addImplement(IsApplicableLayoutUpdateInterface::class);
        }

        $class->addMethod('isApplicable')
            ->addBody(\sprintf('return %s;', $this->expressionLanguage->compile($this->expression)))
            ->addParameter('context')->setType(ContextInterface::class);

        $oldUpdateMethodBody = $visitContext->getUpdateMethodBody();
        $visitContext->setUpdateMethodBody(
            <<<'CODE'
if (!$this->isApplicable($item->getContext())) {
    return;
}
CODE
        );
        $visitContext->appendToUpdateMethodBody($oldUpdateMethodBody);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function endVisit(VisitContext $visitContext): void
    {
    }
}
