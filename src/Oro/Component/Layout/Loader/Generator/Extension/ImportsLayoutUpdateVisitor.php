<?php

namespace Oro\Component\Layout\Loader\Generator\Extension;

use CG\Generator\PhpMethod;

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
        $class->addInterfaceName('Oro\Component\Layout\ImportsAwareLayoutUpdateInterface');
        $setFactoryMethod = PhpMethod::create('getImports');
        $setFactoryMethod->setBody($writer->write('return '.var_export($this->import, true).';')->getContent());
        $class->setMethod($setFactoryMethod);
    }

    /**
     * {@inheritdoc}
     */
    public function endVisit(VisitContext $visitContext)
    {
    }
}
