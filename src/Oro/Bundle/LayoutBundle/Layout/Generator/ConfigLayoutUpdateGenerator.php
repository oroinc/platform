<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

use Oro\Bundle\LayoutBundle\Exception\SyntaxException;
use Oro\Bundle\LayoutBundle\Layout\Generator\Utils\ReflectionUtils;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionCollection;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConfigExpressionCondition;

class ConfigLayoutUpdateGenerator extends AbstractLayoutUpdateGenerator
{
    const NODE_ACTIONS   = 'actions';
    const NODE_CONDITION = 'condition';

    /** @var ReflectionUtils */
    protected $helper;

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
            $actionName = key($actionDefinition);
            $arguments  = isset($actionDefinition[$actionName]) && is_array($actionDefinition[$actionName])
                ? $actionDefinition[$actionName] : [];

            $call = [];
            $this->normalizeActionName($actionName);
            $this->getHelper()->completeArguments($actionName, $arguments);

            array_walk(
                $arguments,
                function (&$arg) {
                    $arg = var_export($arg, true);
                }
            );
            $call[] = sprintf('$%s->%s(', self::PARAM_LAYOUT_MANIPULATOR, $actionName);
            $call[] = implode(', ', $arguments);
            $call[] = ');';

            $body[] = implode(' ', $call);
        }

        return implode("\n", $body);
    }

    /**
     * Validates given resource data, checks that "actions" node exists and consist valid actions.
     *
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function validate(GeneratorData $data)
    {
        $source = $data->getSource();

        if (!(is_array($source) && isset($source[self::NODE_ACTIONS]) && is_array($source[self::NODE_ACTIONS]))) {
            throw new SyntaxException(sprintf('expected array with "%s" node', self::NODE_ACTIONS), $source);
        }

        foreach ($source[self::NODE_ACTIONS] as $nodeNo => $actionDefinition) {
            $path = self::NODE_ACTIONS . '.' . $nodeNo;

            if (!is_array($actionDefinition)) {
                throw new SyntaxException('expected array with action name as key', $actionDefinition, $path);
            }

            $actionName = key($actionDefinition);
            $arguments  = is_array($actionDefinition[$actionName])
                ? $actionDefinition[$actionName] : [$actionDefinition[$actionName]];

            if (strpos($actionName, '@') !== 0) {
                throw new SyntaxException(
                    sprintf('action name should start with "@" symbol, current name "%s"', $actionName),
                    $actionDefinition,
                    $path
                );
            }

            $this->normalizeActionName($actionName);

            if (!$this->getHelper()->hasMethod($actionName)) {
                throw new SyntaxException(
                    sprintf('unknown action "%s", should be one of LayoutManipulatorInterface\'s methods', $actionName),
                    $actionDefinition,
                    $path
                );
            }

            if (!$this->getHelper()->isValidArguments($actionName, $arguments)) {
                throw new SyntaxException($this->getHelper()->getLastError(), $actionDefinition, $path);
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
     * @return ReflectionUtils
     */
    protected function getHelper()
    {
        if (null === $this->helper) {
            $this->helper = new ReflectionUtils('Oro\Component\Layout\LayoutManipulatorInterface');
        }

        return $this->helper;
    }

    /**
     * Removes "@" sign from beginning of action name
     *
     * @param string $actionName
     */
    protected function normalizeActionName(&$actionName)
    {
        $actionName = substr($actionName, 1);
    }
}
