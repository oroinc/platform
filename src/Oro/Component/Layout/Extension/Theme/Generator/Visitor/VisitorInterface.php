<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator\Visitor;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;

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
