<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator\Condition;

use CG\Generator\PhpMethod;
use CG\Generator\PhpProperty;
use CG\Generator\PhpParameter;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;
use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface;

class ConfigExpressionCondition implements ConditionInterface
{
    /** @var array */
    protected $configuration;

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(VisitContext $visitContext)
    {
        $writer = $visitContext->createWriter();
        $class  = $visitContext->getClass();

        $class->addInterfaceName('\Oro\Component\ConfigExpression\ExpressionAssemblerAwareInterface');

        $setAssemblerMethod = PhpMethod::create('setAssembler');
        $setAssemblerMethod->addParameter(
            PhpParameter::create('assembler')
                ->setType('\Oro\Component\ConfigExpression\ExpressionAssembler')
        );
        $setAssemblerMethod->setBody($writer->write('$this->expressionAssembler = $assembler;')->getContent());
        $class->setMethod($setAssemblerMethod);
        $writer->reset();

        $assemblerProperty = PhpProperty::create('expressionAssembler');
        $assemblerProperty->setVisibility(PhpProperty::VISIBILITY_PRIVATE);
        $class->setProperty($assemblerProperty);

        /** @var PhpMethod[] $methods */
        $methods = $class->getMethods();
        $method = $methods[LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME];

        $bodyTemplate = <<<CONTENT
    if (\$this->expressionAssembler) {
        \$expr = \$this->expressionAssembler->assemble(%s);
        \$context = ['context' => $%s->getContext()];
        if (\$expr instanceof \Oro\Component\ConfigExpression\ExpressionInterface && \$expr->evaluate(\$context)) {
            %s
        }
    }
CONTENT;

        $method->setBody(
            $writer->write(
                sprintf(
                    $bodyTemplate,
                    var_export($this->configuration, true),
                    LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM,
                    $method->getBody()
                )
            )
            ->getContent()
        );
    }
}
