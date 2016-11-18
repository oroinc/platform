<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20\Workflow;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class WorkflowsUtil
{
    /**
     * @param string $nodeName
     * @param array $data
     * @return bool
     */
    public static function hasArr($nodeName, array &$data)
    {
        return array_key_exists($nodeName, $data) && is_array($data[$nodeName]);
    }

    /**
     * @param array $data
     * @return bool
     */
    private static function hasWorkflows(array &$data)
    {
        return self::hasArr('workflows', $data);
    }

    /**
     * @param array $data
     * @return \Generator
     */
    public static function workflows(array &$data)
    {
        if (self::hasWorkflows($data)) {
            foreach ($data['workflows'] as $workflowName => $workflowConfig) {
                yield $workflowName => $workflowConfig;
            }
        }
    }

    /**
     * @param array $data
     * @return \Generator
     */
    public static function attributes(array &$data)
    {
        if (self::hasAttributes($data)) {
            foreach ($data[WorkflowConfiguration::NODE_ATTRIBUTES] as $attributeName => $attributeConfig) {
                yield $attributeName => $attributeConfig;
            }
        }
    }

    /**
     * @param array $data
     * @return bool
     */
    private static function hasAttributes(array &$data)
    {
        return self::hasArr(WorkflowConfiguration::NODE_ATTRIBUTES, $data);
    }

    /**
     * @param array $data
     * @return bool
     */
    private static function hasSteps(array &$data)
    {
        return self::hasArr(WorkflowConfiguration::NODE_STEPS, $data);
    }

    /**
     * @param array $data
     * @return \Generator
     */
    public static function steps(array &$data)
    {
        if (self::hasSteps($data)) {
            foreach ($data[WorkflowConfiguration::NODE_STEPS] as $stepName => $stepConfig) {
                yield $stepName => $stepConfig;
            }
        }
    }

    /**
     * @param $data
     * @return \Generator
     */
    public static function transitions($data)
    {
        if (self::hasArr(WorkflowConfiguration::NODE_TRANSITIONS, $data)) {
            foreach ($data[WorkflowConfiguration::NODE_TRANSITIONS] as $transitionName => $transitionConfig) {
                yield  $transitionName => $transitionConfig;
            }
        }
    }
}
