<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionCollection;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConfigExpressionCondition;

class ConfigLayoutUpdateGenerator extends AbstractLayoutUpdateGenerator
{
    const NODE_ACTIONS   = 'actions';
    const NODE_CONDITION = 'condition';

    /** @var \ReflectionClass */
    protected $ref;

    /**
     * {@inheritdoc}
     */
    protected function doGenerateBody($data)
    {
        $body = [];
        foreach ($data as $actionDefinition) {
            $actionName = key($actionDefinition);
            $actionName = substr($actionName, 1);
            $parameters = is_array($actionDefinition[$actionName]) ? $actionDefinition[$actionName] : [];

            $body[] = sprintf('$%s->%s(', self::PARAM_LAYOUT_MANIPULATOR, $actionName);
            foreach ($parameters as $value) {
                $body[] = var_export($value, true) . ',';
            }
            $body[] = ');';
        }

        return implode("\n", $body);
    }

    /**
     * Validates given resource data, checks that "actions" node exists and consist valid actions.
     *
     * {@inheritdoc}
     */
    protected function validate($data)
    {
        if (!(is_array($data) && isset($data[self::NODE_ACTIONS]) && is_array($data[self::NODE_ACTIONS]))) {
            throw new \LogicException(sprintf('Invalid data given, expected array with key "%s"', self::NODE_ACTIONS));
        }

        foreach ($data[self::NODE_ACTIONS] as $k => $actionDefinition) {
            $actionName = key($actionDefinition);

            if (!(is_array($actionDefinition) && $this->isKnownAction($actionName))) {
                throw new \LogicException(sprintf('Invalid action at position: %d, name: %s', $k, $actionName));
            }
        }
    }

    /**
     * Appends given condition expression from "condition" node to condition collection.
     *
     * {@inheritdoc}
     */
    protected function prepareConditionCollection($data, ConditionCollection $conditionCollection)
    {
        if (isset($data[self::NODE_CONDITION])) {
            $conditionCollection->append(new ConfigExpressionCondition($data[self::NODE_CONDITION]));
        }
    }

    /**
     * Checks whether given action is known method of layout manipulator.
     *
     * @param string $actionName
     *
     * @return bool
     */
    private function isKnownAction($actionName)
    {
        if (strpos($actionName, '@') !== 0) {
            return false;
        }

        if (null === $this->ref) {
            $this->ref = new \ReflectionClass('Oro\Component\Layout\LayoutManipulatorInterface');
        }

        $actionName = substr($actionName, 1);

        return $this->ref->hasMethod($actionName);
    }
}
