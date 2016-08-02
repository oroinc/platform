<?php

namespace Oro\Component\Layout\Loader\Generator\Extension;

use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Generator\PhpProperty;

use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;

class ImportLayoutUpdateVisitor implements VisitorInterface
{
    /**
     * {@inheritdoc}
     */
    public function startVisit(VisitContext $visitContext)
    {
        $writer = $visitContext->createWriter();
        $class = $visitContext->getClass();
        $class->addUseStatement('Oro\Component\Layout\ImportLayoutManipulator');
        $class->addInterfaceName('Oro\Component\Layout\LayoutUpdateImportInterface');

        $setFactoryMethod = PhpMethod::create('setImport');
        $setFactoryMethod->addParameter(
            PhpParameter::create('import')
                ->setType('Oro\Component\Layout\Model\LayoutUpdateImport')
        );
        $setFactoryMethod->setBody($writer->write('$this->import = $import;')->getContent());
        $class->setMethod($setFactoryMethod);

        $factoryProperty = PhpProperty::create('import');
        $factoryProperty->setVisibility(PhpProperty::VISIBILITY_PRIVATE);
        $class->setProperty($factoryProperty);

        $setFactoryMethod = PhpMethod::create('setParentUpdate');
        $setFactoryMethod->addParameter(
            PhpParameter::create('parentLayoutUpdate')
                ->setType('\Oro\Component\Layout\ImportsAwareLayoutUpdateInterface')
        );
        $setFactoryMethod->setBody(
            $writer->reset()->write('$this->parentLayoutUpdate = $parentLayoutUpdate;')
            ->getContent()
        );
        $class->setMethod($setFactoryMethod);

        $factoryProperty = PhpProperty::create('parentLayoutUpdate');
        $factoryProperty->setVisibility(PhpProperty::VISIBILITY_PRIVATE);
        $class->setProperty($factoryProperty);


        $visitContext->getUpdateMethodWriter()
            ->writeln('if (null === $this->import) {')
            ->writeln(
                '    throw new \\RuntimeException(\'Missing import configuration for layout update\');'
            )
            ->writeln('}')
            ->writeln('')
            ->writeln('if ($this->parentLayoutUpdate instanceof Oro\Component\Layout\IsApplicableLayoutUpdateInterface')
            ->writeln('    && !$this->parentLayoutUpdate->isApplicable()) {')
            ->writeln('    return;')
            ->writeln('}')
            ->writeln('')
            ->writeln('$layoutManipulator  = new ImportLayoutManipulator($layoutManipulator, $this->import);')
            ->indent();
    }

    /**
     * {@inheritdoc}
     */
    public function endVisit(VisitContext $visitContext)
    {
    }
}
