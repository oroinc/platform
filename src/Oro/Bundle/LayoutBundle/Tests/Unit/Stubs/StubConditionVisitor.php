<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Stubs;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;
use Oro\Bundle\LayoutBundle\Layout\Generator\Visitor\VisitorInterface;

class StubConditionVisitor implements VisitorInterface
{
    /**
     * {@inheritdoc}
     */
    public function startVisit(VisitContext $visitContext)
    {
        $visitContext->getUpdateMethodWriter()
            ->writeln('if (true) {')
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
