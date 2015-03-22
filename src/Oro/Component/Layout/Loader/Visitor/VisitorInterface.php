<?php

namespace Oro\Component\Layout\Extension\Theme\Generator\Visitor;

use Oro\Component\Layout\Extension\Theme\Generator\VisitContext;

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
