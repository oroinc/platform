<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Checker;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class ConfigurationChecker
{
    use ContextAccessorAwareTrait;

    /**
     * @param ContextAccessorInterface $contextAccessor
     */
    public function __construct(ContextAccessorInterface $contextAccessor)
    {
        $this->contextAccessor = $contextAccessor;
    }

    /**
     * @param array $configuration
     * @return bool
     */
    public function isClean(array $configuration)
    {
        return $this->isTransitionsClean($configuration) && $this->isTransitionDefinitionsClean($configuration);
    }

    /**
     * @param array $configuration
     * @return bool
     */
    protected function isTransitionsClean(array $configuration)
    {
        $paths = [new PropertyPath('form_options.init_actions'), new PropertyPath('form_options.form_init')];

        return $this->isConfigurationClean(
            $configuration,
            WorkflowConfiguration::NODE_TRANSITIONS,
            $paths
        );
    }

    /**
     * @param array $configuration
     * @return bool
     */
    protected function isTransitionDefinitionsClean(array $configuration)
    {
        $paths = [
            new PropertyPath('preactions'),
            new PropertyPath('preconditions'),
            new PropertyPath('pre_conditions'),
            new PropertyPath('conditions'),
            new PropertyPath('actions'),
            new PropertyPath('post_actions')
        ];

        return $this->isConfigurationClean(
            $configuration,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
            $paths
        );
    }

    /**
     * @param array $configuration
     * @param string $nodeName
     * @param array $paths
     * @return bool
     */
    private function isConfigurationClean(array $configuration, $nodeName, array $paths)
    {
        $transitions = $this->getConfigurationOption($configuration, $nodeName, []);

        foreach ($transitions as $config) {
            if ($this->isPathsNotEmpty($config, $paths)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $configuration
     * @param array $paths
     * @return bool
     */
    private function isPathsNotEmpty(array $configuration, array $paths)
    {
        $result = array_filter(
            array_map(
                function (PropertyPath $path) use ($configuration) {
                    return (bool)$this->resolveValue($configuration, $path, false);
                },
                $paths
            )
        );

        return (bool)$result;
    }

    /**
     * @param array $options
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getConfigurationOption(array $options, $key, $default = null)
    {
        return array_key_exists($key, $options) ? $options[$key] : $default;
    }
}
