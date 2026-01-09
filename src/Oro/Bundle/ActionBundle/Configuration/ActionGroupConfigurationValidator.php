<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Collections\Collection;

/**
 * Validates action group configuration.
 *
 * This validator ensures that action group configurations conform to expected
 * standards. Currently, it performs no validation but provides a placeholder
 * for future validation logic.
 */
class ActionGroupConfigurationValidator implements ConfigurationValidatorInterface
{
    #[\Override]
    public function validate(array $configuration, ?Collection $errors = null)
    {
        // nothing
    }
}
