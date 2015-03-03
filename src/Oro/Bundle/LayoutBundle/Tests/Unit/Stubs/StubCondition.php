<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Stubs;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionInterface;

class StubCondition implements ConditionInterface
{
    /**
     * {@inheritdoc}
     */
    public function startVisit(VisitContext $visitContext)
    {
        $visitContext->getWriter()
            ->writeln('if (true) {')
            ->indent();
    }

    /**
     * {@inheritdoc}
     */
    public function endVisit(VisitContext $visitContext)
    {
        $visitContext->getWriter()
            ->outdent()
            ->writeln('}');
    }
}
