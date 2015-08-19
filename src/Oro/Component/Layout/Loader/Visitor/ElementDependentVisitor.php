<?php

namespace Oro\Component\Layout\Loader\Visitor;

use CG\Generator\PhpMethod;

use Oro\Component\Layout\Loader\Generator\VisitContext;

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
            ->addInterfaceName('Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface');
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
