<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension\Generator;

use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionConditionVisitor implements VisitorInterface
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
        $writer = $visitContext->createWriter();
        $class  = $visitContext->getClass();
        $class->addInterfaceName('Oro\Component\Layout\IsApplicableLayoutUpdateInterface');

        $setFactoryMethod = PhpMethod::create('isApplicable');
        $setFactoryMethod->addParameter(
            PhpParameter::create('context')
                ->setType('\Oro\Component\Layout\ContextInterface')
        );
        $setFactoryMethod->setBody(
            $writer
                ->reset()
                ->write(
                    sprintf('return %s;', $this->expressionLanguage->compile($this->expression))
                )
                ->getContent()
        );
        $class->setMethod($setFactoryMethod);

        $updateMethodBody = $visitContext->getUpdateMethodWriter()->getContent();
        $visitContext->getUpdateMethodWriter()
            ->reset()
            ->writeln('if (!$this->isApplicable($item->getContext())) {')
            ->indent()
            ->writeln('return;')
            ->outdent()
            ->writeln('}')
            ->write($updateMethodBody);
    }

    /**
     * {@inheritdoc}
     */
    public function endVisit(VisitContext $visitContext)
    {
    }
}
