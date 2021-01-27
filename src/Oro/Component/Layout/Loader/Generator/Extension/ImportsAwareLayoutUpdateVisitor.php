<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Loader\Generator\Extension;

use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;

/**
 * This visitor is used by \Oro\Component\Layout\Loader\Generator\Extension\ImportsLayoutUpdateExtension.
 */
class ImportsAwareLayoutUpdateVisitor implements VisitorInterface
{
    protected array $imports;

    public function __construct(array $imports)
    {
        $this->imports = $imports;
    }

    public function startVisit(VisitContext $visitContext): void
    {
        $class = $visitContext->getClass();
        $class->addImplement(ImportsAwareLayoutUpdateInterface::class);
        $class->addMethod('getImports')->addBody('return '.var_export($this->imports, true).';');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function endVisit(VisitContext $visitContext): void
    {
    }
}
