<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Loader\Visitor;

use Oro\Component\Layout\Loader\Generator\VisitContext;

/**
 * Interface for layout loader visitors
 */
interface VisitorInterface
{
    public function startVisit(VisitContext $visitContext): void;

    public function endVisit(VisitContext $visitContext): void;
}
