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
    protected function doGenerateBody(GeneratorData $data)
    {
        $body   = [];
        $source = $data->getSource();

        if ($data->getFilename()) {
            $body[] = '// filename: ' . $data->getFilename();
        }

        foreach ($source[self::NODE_ACTIONS] as $actionDefinition) {
            $call           = [];
            $fullActionName = key($actionDefinition);
            $actionName     = substr($fullActionName, 1);
            $parameters     = isset($actionDefinition[$fullActionName]) && is_array($actionDefinition[$fullActionName])
                ? $actionDefinition[$fullActionName] : [];

            array_walk(
                $parameters,
                function (&$param) {
                    $param = var_export($param, true);
                }
            );
            $call[] = sprintf('$%s->%s(', self::PARAM_LAYOUT_MANIPULATOR, $actionName);
            $call[] = implode(', ', $parameters);
            $call[] = ');';

            $body[] = implode(' ', $call);
        }

        return implode("\n", $body);
    }

    /**
     * Validates given resource data, checks that "actions" node exists and consist valid actions.
     *
     * {@inheritdoc}
     */
    protected function validate(GeneratorData $data)
    {
        $source = $data->getSource();

        if (!(is_array($source) && isset($source[self::NODE_ACTIONS]) && is_array($source[self::NODE_ACTIONS]))) {
            throw new \LogicException(sprintf('Invalid data given, expected array with key "%s"', self::NODE_ACTIONS));
        }

        foreach ($source[self::NODE_ACTIONS] as $k => $actionDefinition) {
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
    protected function prepareConditionCollection(GeneratorData $data, ConditionCollection $conditionCollection)
    {
        $source = $data->getSource();

        if (isset($source[self::NODE_CONDITION])) {
            $conditionCollection->append(new ConfigExpressionCondition($source[self::NODE_CONDITION]));
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
