<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Collections\Collection;

class ActionGroupConfigurationValidator implements ConfigurationValidatorInterface
{
    #[\Override]
    public function validate(array $configuration, ?Collection $errors = null)
    {
        // nothing
    }
}
