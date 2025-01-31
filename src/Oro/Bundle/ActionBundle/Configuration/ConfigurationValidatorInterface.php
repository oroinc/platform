<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Collections\Collection;

interface ConfigurationValidatorInterface
{
    public function validate(array $configuration, ?Collection $errors = null);
}
