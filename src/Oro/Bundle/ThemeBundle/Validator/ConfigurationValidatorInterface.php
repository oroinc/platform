<?php

namespace Oro\Bundle\ThemeBundle\Validator;

/**
 * Represents a validator for the configuration of themes.
 */
interface ConfigurationValidatorInterface
{
    /**
     * Validates the configuration of themes.
     *
     * @return string[] The list of constraint violation messages
     */
    public function validate(array $config): array;
}
