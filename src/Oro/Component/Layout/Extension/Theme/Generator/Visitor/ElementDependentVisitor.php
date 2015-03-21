<?php

namespace Oro\Component\Layout\Extension\Theme\Generator\Visitor;

use CG\Generator\PhpMethod;

use Oro\Component\Layout\Extension\Theme\Generator\VisitContext;

class ElementDependentVisitor implements VisitorInterface
{
    /** @var string */
    protected $elementId;

    /**
     * @param $elementId
     */
    public function __construct($elementId)
    {
        $this->elementId = $elementId;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisit(VisitContext $visitContext)
    {
        $visitContext
            ->getClass()
            ->addInterfaceName('Oro\Component\Layout\Extension\Theme\Generator\ElementDependentLayoutUpdateInterface');
    }

    /**
     * {@inheritdoc}
     */
    public function endVisit(VisitContext $visitContext)
    {
        $writer = $visitContext->createWriter();
        $writer->writeln(sprintf('return \'%s\';', $this->elementId));

        $method = PhpMethod::create('getElement');
        $method->setBody($writer->getContent());

        $visitContext->getClass()->setMethod($method);
    }
}
