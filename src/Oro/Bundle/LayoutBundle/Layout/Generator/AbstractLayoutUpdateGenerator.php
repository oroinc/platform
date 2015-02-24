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
        $this->validate($data);
        $this->prepareConditionCollection($data, $conditionCollection);

        $class   = PhpClass::create($className);
        $visitor = new VisitContext($class);

        $class->addInterfaceName('Oro\Component\Layout\LayoutUpdateInterface');

        $method = PhpMethod::create(LayoutUpdateGeneratorInterface::UPDATE_METHOD_NAME);

        $manipulatorParameter = PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_MANIPULATOR);
        $manipulatorParameter->setType('Oro\Component\Layout\LayoutManipulatorInterface');
        $method->addParameter($manipulatorParameter);

        $layoutItemParameter = PhpParameter::create(LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM);
        $layoutItemParameter->setType('Oro\Component\Layout\LayoutItemInterface');
        $method->addParameter($layoutItemParameter);

        $method->setBody($visitor->createWriter()->write(trim($this->doGenerateBody($data)))->getContent());
        $class->setMethod($method);

        /** @var ConditionInterface $condition */
        foreach ($conditionCollection as $condition) {
            $condition->visit($visitor);
        }

        $strategy = new DefaultGeneratorStrategy();

        return "<?php\n\n" . $strategy->generate($class);
    }

    /**
     * Performs code generation itself based on data given
     *
     * @param GeneratorData $data
     *
     * @return string
     */
    abstract protected function doGenerateBody(GeneratorData $data);

    /**
     * Validates given resource data. Should throw exception if error found.
     * Empty implementation, could be overridden in descendants.
     *
     * @param GeneratorData $source
     *
     * @return void
     */
    protected function validate(GeneratorData $source)
    {
    }

    /**
     * Do preparation of condition collection based on resource data.
     * Empty implementation, could be overridden in descendants.
     *
     * @param GeneratorData       $data
     * @param ConditionCollection $conditionCollection
     *
     * @return void
     */
    protected function prepareConditionCollection(GeneratorData $data, ConditionCollection $conditionCollection)
    {
    }
}
