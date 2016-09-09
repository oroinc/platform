<?php

namespace Oro\Component\Layout\Loader\Generator\Extension;

use CG\Generator\PhpMethod;

use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;

class ImportsAwareLayoutUpdateVisitor implements VisitorInterface
{
    /**
     * @var array
     */
    protected $imports;

    /**
     * @param $imports
     */
    public function __construct($imports)
    {
        $this->imports = $imports;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisit(VisitContext $visitContext)
    {
        $writer = $visitContext->createWriter();
        $class = $visitContext->getClass();
        $class->addInterfaceName('Oro\Component\Layout\ImportsAwareLayoutUpdateInterface');
        $setFactoryMethod = PhpMethod::create('getImports');
        $setFactoryMethod->setBody($writer->write('return '.var_export($this->imports, true).';')->getContent());
        $class->setMethod($setFactoryMethod);
    }

    /**
     * {@inheritdoc}
     */
    public function endVisit(VisitContext $visitContext)
    {
    }
}
