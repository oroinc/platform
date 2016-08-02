<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension\Generator;

use CG\Generator\PhpMethod;
use CG\Generator\PhpProperty;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;

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

        $factoryProperty = PhpProperty::create('applicable');
        $factoryProperty->setVisibility(PhpProperty::VISIBILITY_PRIVATE);
        $factoryProperty->setDefaultValue(false);
        $class->setProperty($factoryProperty);
        $setFactoryMethod = PhpMethod::create('isApplicable');
        $setFactoryMethod->setBody($writer->reset()->write('return $this->applicable;')->getContent());
        $class->setMethod($setFactoryMethod);

        $visitContext->getUpdateMethodWriter()
            ->writeln(
                sprintf(
                    '$context = $%s->getContext();',
                    LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM
                )
            )
            ->writeln(sprintf('if (%s) {', $this->expressionLanguage->compile($this->expression)))
            ->indent()
            ->writeln('$this->applicable = true;');
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
