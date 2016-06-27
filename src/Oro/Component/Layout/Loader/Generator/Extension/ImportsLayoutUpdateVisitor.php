<?php

namespace Oro\Component\Layout\Loader\Generator\Extension;

use CG\Generator\PhpMethod;
use CG\Generator\PhpProperty;
use CG\Generator\PhpParameter;

use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;

class ImportsLayoutUpdateVisitor implements VisitorInterface
{
    /**
     * @var array
     */
    protected $import;

    /**
     * @param $import
     */
    public function __construct($import)
    {
        $this->import = $import;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisit(VisitContext $visitContext)
    {
        $writer = $visitContext->createWriter();
        $class  = $visitContext->getClass();
        $setFactoryMethod = PhpMethod::create('__construct');
        $setFactoryMethod->addParameter(
            PhpParameter::create('import')
        );
        $setFactoryMethod->setBody($writer->write('$this->import = $import;')->getContent());
        $class->setMethod($setFactoryMethod);
        $factoryProperty = PhpProperty::create('import');
        $factoryProperty->setVisibility(PhpProperty::VISIBILITY_PRIVATE);
        $class->setProperty($factoryProperty);

        $class->addInterfaceName('Oro\Component\Layout\ImportsAwareLayoutUpdateInterface');
        $visitContext->getUpdateMethodWriter()
            ->writeln('if (null === $this->import) {')
            ->writeln('    throw new \\RuntimeException(\'Missing import for layout update\');')
            ->writeln('}');

    }

    /**
     * {@inheritdoc}
     */
    public function endVisit(VisitContext $visitContext)
    {
        $visitContext->getUpdateMethodWriter()
            ->writeln('return $this->import;');
    }
}
