<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Loader\Generator\Extension;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\IsApplicableLayoutUpdateInterface;
use Oro\Component\Layout\LayoutUpdateImportInterface;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;
use Oro\Component\Layout\Model\LayoutUpdateImport;

/**
 * This visitor is used by \Oro\Component\Layout\Loader\Generator\Extension\ImportsLayoutUpdateExtension.
 */
class ImportLayoutUpdateVisitor implements VisitorInterface
{
    public function startVisit(VisitContext $visitContext): void
    {
        $class = $visitContext->getClass();
        $class->addImplement(LayoutUpdateImportInterface::class);
        if (!\in_array(IsApplicableLayoutUpdateInterface::class, $class->getImplements())) {
            $class->addImplement(IsApplicableLayoutUpdateInterface::class);
        }

        $class->addMethod('isApplicable')
            ->addBody('return true;')
            ->addParameter('context')->setType(ContextInterface::class);

        $class->addMethod('getImport')
            ->addBody('return $this->import;');

        $class->addMethod('setImport')
            ->addBody('$this->import = $import;')
            ->addParameter('import')->setType(LayoutUpdateImport::class);

        $class->addProperty('import')->setPrivate();

        $class->addMethod('setParentUpdate')
            ->addBody('$this->parentLayoutUpdate = $parentLayoutUpdate;')
            ->addParameter('parentLayoutUpdate')->setType(ImportsAwareLayoutUpdateInterface::class);

        $class->addProperty('parentLayoutUpdate')->setPrivate();

        $visitContext->appendToUpdateMethodBody(
            <<<'CODE'
if (null === $this->import) {
    throw new \RuntimeException('Missing import configuration for layout update');
}

if ($this->parentLayoutUpdate instanceof \Oro\Component\Layout\IsApplicableLayoutUpdateInterface
    && !$this->parentLayoutUpdate->isApplicable($item->getContext())) {
    return;
}

$layoutManipulator = new \Oro\Component\Layout\ImportLayoutManipulator($layoutManipulator, $this->import);
CODE
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function endVisit(VisitContext $visitContext): void
    {
    }
}
