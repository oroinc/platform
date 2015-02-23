<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Stubs;

use CG\Generator\PhpMethod;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionInterface;
use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGeneratorInterface;

class StubCondition implements ConditionInterface
{
    /**
     * {@inheritdoc}
     */
    public function visit(VisitContext $visitContext)
    {
        /** @var PhpMethod[] $methods */
        $methods = $visitContext->getClass()->getMethods();
        $method  = $methods[LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME];
        $bodyTemplate  = <<<CONTENT
if (true) {
    %s
}
CONTENT;

        $method->setBody(
            $visitContext
                ->createWriter()
                ->write(sprintf($bodyTemplate, $method->getBody()))
                ->getContent()
        );
    }
}
