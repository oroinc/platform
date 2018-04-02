<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Helper for cloning workflow definitions
 */
class WorkflowDefinitionClonerHelper
{
    /**
     * @param array $configuration
     *
     * @return array
     */
    public static function parseVariableDefinitions(array $configuration)
    {
        $definitions = [];
        foreach ($configuration as $name => $options) {
            if (empty($options)) {
                $options = [];
            }

            $definition = [
                'label' => self::getOption('label', $options),
                'type' => self::getOption('type', $options),
                'value' => self::getOption('value', $options),
                'options' => self::getOption('options', $options, []),
            ];

            $definitions[$name] = $definition;
        }

        return $definitions;
    }

    /**
     * @param string $key
     * @param mixed  $options
     * @param mixed  $default
     *
     * @return mixed|null
     */
    public static function getOption($key, $options, $default = null)
    {
        if (empty($options)) {
            return $default;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            $value = $accessor->getValue($options, self::getAccessorKey($key));
            if (!$value) {
                return $default;
            }

            return $value;
        } catch (\RuntimeException $e) {
            return $default;
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected static function getAccessorKey($key)
    {
        $parts = explode('.', $key);
        $accessorKey = '';
        foreach ($parts as $part) {
            if (empty($part)) {
                $part = 0;
            }
            $accessorKey .= sprintf('[%s]', $part);
        }

        return $accessorKey;
    }

    /**
     * Copy variable configuration
     *
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $source
     *
     * @return array
     */
    public static function copyConfigurationVariables(WorkflowDefinition $definition, WorkflowDefinition $source)
    {
        $definitionsNode = WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS;
        $variablesNode = WorkflowConfiguration::NODE_VARIABLES;

        $newConfig = $source->getConfiguration();
        $existingConfig = $definition->getConfiguration();
        if (!isset($newConfig[$definitionsNode], $existingConfig[$definitionsNode])) {
            return $newConfig;
        }

        $newDefinition = $newConfig[$definitionsNode];
        $existingDefinition = $existingConfig[$definitionsNode];
        if (!isset($newDefinition[$variablesNode], $existingDefinition[$variablesNode])) {
            return $newConfig;
        }

        $newVariables = $newDefinition[$variablesNode];
        $existingVariables = $existingDefinition[$variablesNode];

        return self::mergeConfigurationVariablesValue($newConfig, $existingVariables, $newVariables);
    }

    /**
     * Retain variables value if:
     *  - 'type' didn't change
     *
     * And in case of objects and entities:
     *  - 'class' didn't change
     *
     * And in case of entities:
     *  - 'options.identifier' didn't change
     *
     * @param array $configuration
     * @param array $definition
     * @param array $source
     *
     * @return array
     */
    private static function mergeConfigurationVariablesValue(array $configuration, $definition, $source)
    {
        $definitionsNode = WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS;
        $variablesNode = WorkflowConfiguration::NODE_VARIABLES;

        $sourceParsed = self::parseVariableDefinitions($source);
        $definitionParsed = self::parseVariableDefinitions($definition);

        foreach ($sourceParsed as $name => $sourceVariable) {
            // nothing to copy
            if (!isset($definitionParsed[$name])) {
                continue;
            }

            $existingVariable = $definitionParsed[$name];
            if (self::isVariableValueRetainable($existingVariable, $sourceVariable)) {
                $sourceVariable['value'] = self::getOption('value', $existingVariable);
                $configuration[$definitionsNode][$variablesNode][$name] = $sourceVariable;
            }
        }

        return $configuration;
    }

    /**
     * @param array $definition
     * @param array $source
     *
     * @return bool
     */
    private static function isVariableValueRetainable($definition, $source)
    {
        // types don't match
        $newType = self::getOption('type', $source);
        if ($newType !== self::getOption('type', $definition)) {
            return false;
        }

        if (!in_array($newType, ['object', 'entity'], true)) {
            return true;
        }

        // class is not defined or changed
        $newClass = self::getOption('options.class', $source);
        $existingClass = self::getOption('options.class', $definition);

        if (!$newClass || !$existingClass || $newClass !== $existingClass) {
            return false;
        }

        if ('entity' !== $newType) {
            return true;
        }

        // entity identifier is not defined or changed
        $newIdentifier = self::getOption('options.identifier', $source);
        $existingIdentifier = self::getOption('options.identifier', $definition);

        return !(!$newClass || !$existingClass || $newIdentifier !== $existingIdentifier);
    }
}
