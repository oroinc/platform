<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class PermissionConfigurationValidator
{
    /**
     * @param array $configuration
     * @return array
     */
    public function validate(array $configuration)
    {
        foreach (array_keys($configuration) as $name) {
            $this->validateName($name);
        }
    }

    /**
     * @param string $name
     * @throws InvalidConfigurationException
     */
    protected function validateName($name)
    {
        if (!preg_match('/^\w[\w\-:]*$/D', $name)) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The permission name "%s" contains illegal characters. ' .
                    'Names should start with a letter, digit or underscore and only contain letters, digits, ' .
                    'numbers, underscores ("_"), hyphens ("-") and colons (":").',
                    $name
                )
            );
        }
    }
}
