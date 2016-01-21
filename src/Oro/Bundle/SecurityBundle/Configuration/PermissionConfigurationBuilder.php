<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Oro\Bundle\SecurityBundle\Entity\PermissionDefinition;

class PermissionConfigurationBuilder
{
    /**
     * @param array $configuration
     * @return PermissionDefinition[]
     */
    public function buildPermissionDefinitions(array $configuration)
    {
        $definitions = array();
        foreach ($configuration as $name => $definitionConfiguration) {
            $definitions[] = $this->buildPermissionDefinition($name, $definitionConfiguration);
        }

        return $definitions;
    }

    /**
     * @param $name
     * @param array $configuration
     * @return PermissionDefinition
     */
    public function buildPermissionDefinition($name, array $configuration)
    {
        $this->assertConfigurationOptions($configuration, ['label']);

        $definition = new PermissionDefinition();
        $definition
            ->setName($name)
            ->setLabel($configuration['label']);

        return $definition;
    }

    /**
     * @param array $configuration
     * @param array $requiredOptions
     * @throws MissedRequiredOptionException
     */
    protected function assertConfigurationOptions(array $configuration, array $requiredOptions)
    {
        foreach ($requiredOptions as $optionName) {
            if (!isset($configuration[$optionName])) {
                throw new MissedRequiredOptionException(sprintf('Configuration option "%s" is required', $optionName));
            }
        }
    }

    /**
     * @param array $options
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfigurationOption(array $options, $key, $default = null)
    {
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }
        return $default;
    }
}
