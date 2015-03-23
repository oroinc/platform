<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Stubs;

use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;

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
