<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator\Condition;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;

interface ConditionInterface
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
