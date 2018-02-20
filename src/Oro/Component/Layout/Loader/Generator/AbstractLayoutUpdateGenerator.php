<?php

namespace Oro\Component\Layout\Loader\Generator;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;

abstract class AbstractLayoutUpdateGenerator implements LayoutUpdateGeneratorInterface
{
    /** @var VisitorCollection */
    private $visitorCollection;

    /**
     * {@inheritdoc}
     */
    public function generate($className, GeneratorData $data, VisitorCollection $visitorCollection = null)
    {
        $this->visitorCollection = $visitorCollection ?: new VisitorCollection();

        $this->prepare($data, $this->visitorCollection);
        $this->validate($data);

        $class        = PhpClass::create($className);
        $visitContext = new VisitContext($class);

        if ($data->getFilename()) {
            $writer = $visitContext->createWriter();
            $writer
                ->writeln('/**')
                ->writeln(' * Filename: '.$data->getFilename())
                ->writeln(' */');

            $class->setDocblock($writer->getContent());
        }

        $class->addInterfaceName('Oro\Component\Layout\LayoutUpdateInterface');

        $method = PhpMethod::create(LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME);

        $manipulatorParameter = PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_MANIPULATOR);
        $manipulatorParameter->setType('Oro\Component\Layout\LayoutManipulatorInterface');
        $method->addParameter($manipulatorParameter);

        $layoutItemParameter = PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM);
        $layoutItemParameter->setType('Oro\Component\Layout\LayoutItemInterface');
        $method->addParameter($layoutItemParameter);

        /** @var VisitorInterface $condition */
        foreach ($this->visitorCollection as $condition) {
            $condition->startVisit($visitContext);
        }

        $writer = $visitContext->getUpdateMethodWriter();
        $writer->writeLn(trim($this->doGenerateBody($data)));

        /** @var VisitorInterface $condition */
        foreach ($this->visitorCollection as $condition) {
            $condition->endVisit($visitContext);
        }

        $method->setBody($writer->getContent());
        $class->setMethod($method);

        $strategy = new DefaultGeneratorStrategy();

        return "<?php\n\n".$strategy->generate($class);
    }

    /**
     * @return VisitorCollection
     */
    public function getVisitorCollection()
    {
        return $this->visitorCollection;
    }

    /**
     * Performs code generation itself based on source data given
     *
     * @param GeneratorData $data
     */
    abstract protected function doGenerateBody(GeneratorData $data);

    /**
     * Do preparation of data and visitor collection based on resource data.
     * Empty implementation, could be overridden in descendants.
     *
     * @param GeneratorData     $data
     * @param VisitorCollection $visitorCollection
     */
    protected function prepare(GeneratorData $data, VisitorCollection $visitorCollection)
    {
    }

    /**
     * Validates given resource data. Should throw exception if error found.
     * Empty implementation, could be overridden in descendants.
     *
     * @param GeneratorData $data
     */
    protected function validate(GeneratorData $data)
    {
    }
}
