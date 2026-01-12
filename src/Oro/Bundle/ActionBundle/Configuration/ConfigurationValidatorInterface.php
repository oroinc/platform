<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Collections\Collection;

/**
 * Defines the contract for validating action configuration.
 */
interface ConfigurationValidatorInterface
{
    public function validate(array $configuration, ?Collection $errors = null);
}
