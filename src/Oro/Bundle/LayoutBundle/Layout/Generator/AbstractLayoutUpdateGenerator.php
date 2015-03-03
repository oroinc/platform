<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Core\DefaultGeneratorStrategy;

use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionCollection;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionInterface;

abstract class AbstractLayoutUpdateGenerator implements LayoutUpdateGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate($className, GeneratorData $data, ConditionCollection $conditionCollection)
    {
        $this->prepare($data, $conditionCollection);
        $this->validate($data);

        $class   = PhpClass::create($className);
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

        /** @var ConditionInterface $condition */
        foreach ($conditionCollection as $condition) {
            $condition->startVisit($visitContext);
        }

        $writer = $visitContext->getWriter();
        $writer->writeLn(trim($this->doGenerateBody($data)));

        /** @var ConditionInterface $condition */
        foreach ($conditionCollection as $condition) {
            $condition->endVisit($visitContext);
        }

        $method->setBody($writer->getContent());
        $class->setMethod($method);

        $strategy = new DefaultGeneratorStrategy();

        return "<?php\n\n".$strategy->generate($class);
    }

    /**
     * Performs code generation itself based on source data given
     *
     * @param GeneratorData $data
     */
    abstract protected function doGenerateBody(GeneratorData $data);

    /**
     * Do preparation of data and condition collection based on resource data.
     * Empty implementation, could be overridden in descendants.
     *
     * @param GeneratorData       $data
     * @param ConditionCollection $conditionCollection
     */
    protected function prepare(GeneratorData $data, ConditionCollection $conditionCollection)
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
