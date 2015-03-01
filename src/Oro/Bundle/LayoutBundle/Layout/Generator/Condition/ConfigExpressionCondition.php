<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator\Condition;

use CG\Generator\PhpMethod;
use CG\Generator\PhpProperty;
use CG\Generator\PhpParameter;

use Oro\Component\ConfigExpression\ExpressionInterface;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;
use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface;

class ConfigExpressionCondition implements ConditionInterface
{
    /** @var ExpressionInterface */
    protected $expression;

    /**
     * @param ExpressionInterface $expression
     */
    public function __construct(ExpressionInterface $expression)
    {
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(VisitContext $visitContext)
    {
        $writer = $visitContext->createWriter();
        $class  = $visitContext->getClass();

        $class->addInterfaceName('\Oro\Bundle\LayoutBundle\Layout\Generator\ExpressionFactoryAwareInterface');

        $setAssemblerMethod = PhpMethod::create('setExpressionFactory');
        $setAssemblerMethod->addParameter(
            PhpParameter::create('expressionFactory')
                ->setType('\Oro\Component\ConfigExpression\ExpressionFactoryInterface')
        );
        $setAssemblerMethod->setBody($writer->write('$this->expressionFactory = $expressionFactory;')->getContent());
        $class->setMethod($setAssemblerMethod);
        $writer->reset();

        $assemblerProperty = PhpProperty::create('expressionFactory');
        $assemblerProperty->setVisibility(PhpProperty::VISIBILITY_PRIVATE);
        $class->setProperty($assemblerProperty);

        /** @var PhpMethod[] $methods */
        $methods = $class->getMethods();
        $method = $methods[LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME];

        $bodyTemplate = <<<CONTENT
    if (null === \$this->expressionFactory) {
        throw new \\RuntimeException('Missing expression factory for layout update');
    }

    \$expr = %s;
    \$context = ['context' => $%s->getContext()];
    if (\$expr->evaluate(\$context)) {
        %s
    }
CONTENT;

        $method->setBody(
            $writer
                ->write(
                    sprintf(
                        $bodyTemplate,
                        $this->expression->compile('$this->expressionFactory'),
                        LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM,
                        $method->getBody()
                    )
                )
                ->getContent()
        );
    }
}
