<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionCollection;

interface LayoutUpdateGeneratorInterface
{
    const UPDATE_METHOD_NAME = 'updateLayout';

    const PARAM_LAYOUT_MANIPULATOR = 'layoutManipulator';
    const PARAM_LAYOUT_ITEM        = 'item';

    /**
     * Generates valid PHP class that is instance of "Oro\Component\Layout\LayoutUpdateInterface" based on given data.
     *
     * @param string              $className           Class name for newly generated PHP source
     * @param GeneratorData       $data                Data consist actions which should be generated as PHP code
     * @param ConditionCollection $conditionCollection Collection of conditions that are should be allowed for
     *                                                 the actions to be performed
     *
     * @return string
     */
    public function generate($className, GeneratorData $data, ConditionCollection $conditionCollection);
}
