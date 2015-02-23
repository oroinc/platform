<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator\Condition;

use CG\Generator\PhpMethod;
use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface;
use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;

class ConfigExpressionCondition implements ConditionInterface
{
    const ASSEMBLER_PROPERTY_NAME = 'expressionAssembler';

    /**
     * {@inheritdoc}
     */
    public function visit(VisitContext $visitContext)
    {
        /** @var PhpMethod[] $methods */
        $methods = $visitContext->getClass()->getMethods();
        $method  = $methods[LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME];
        $bodyTemplate = <<<CONTENT

CONTENT;
    }
}
