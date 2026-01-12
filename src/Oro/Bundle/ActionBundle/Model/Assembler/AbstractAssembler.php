<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Bundle\ActionBundle\Exception\MissedRequiredOptionException;

/**
 * Provides common functionality for assembling action configuration objects.
 *
 * This base class implements helper methods for validating required options and safely accessing
 * configuration values with defaults. Subclasses should extend this to create specific assemblers
 * for different action configuration types (e.g., operations, buttons, conditions).
 */
abstract class AbstractAssembler
{
    /**
     * @param array $options
     * @param array $requiredOptions
     * @param string $path
     * @throws MissedRequiredOptionException
     */
    protected function assertOptions(array $options, array $requiredOptions, $path = null)
    {
        foreach ($requiredOptions as $optionName) {
            if (empty($options[$optionName])) {
                $message = 'Option "%s" is required';
                if ($path) {
                    $message = sprintf('%s at "%s"', $message, $path);
                }

                throw new MissedRequiredOptionException(sprintf($message, $optionName));
            }
        }
    }

    /**
     * @param array $options
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getOption(array $options, $key, $default = null)
    {
        return array_key_exists($key, $options) ? $options[$key] : $default;
    }
}
