<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

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
            $accessorKey = $key;
            if (is_array($options)) {
                $parts = explode('.', $key);
                $accessorKey = '';
                foreach ($parts as $part) {
                    if (empty($part)) {
                        $part = 0;
                    }
                    $accessorKey .= sprintf('[%s]', $part);
                }
            }

            $value = $accessor->getValue($options, $accessorKey);
            if (!$value) {
                return $default;
            }

            return $value;
        } catch (\RuntimeException $e) {
            return $default;
        }
    }
}
