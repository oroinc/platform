<?php

declare(strict_types=1);

namespace Oro\Component\Layout\Loader\Visitor;

use Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface;
use Oro\Component\Layout\Loader\Generator\VisitContext;

/**
 * This visitor adds getElement() getter. It is used by default for all elements.
 */
class ElementDependentVisitor implements VisitorInterface
{
    protected string $elementId;

    public function __construct(string $elementId)
    {
        $this->elementId = $elementId;
    }

    #[\Override]
    public function startVisit(VisitContext $visitContext): void
    {
        $visitContext->getClass()->addImplement(ElementDependentLayoutUpdateInterface::class);
    }

    #[\Override]
    public function endVisit(VisitContext $visitContext): void
    {
        $visitContext->getClass()
            ->addMethod('getElement')->addBody(\sprintf('return \'%s\';', $this->elementId));
    }
}
