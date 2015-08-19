<?php

namespace Oro\Component\Layout\Loader\Visitor;

use Oro\Component\Layout\Loader\Generator\VisitContext;

interface VisitorInterface
{
    /**
     * @param VisitContext $visitContext
     */
    public function startVisit(VisitContext $visitContext);

    /**
     * @param VisitContext $visitContext
     */
    public function endVisit(VisitContext $visitContext);
}
