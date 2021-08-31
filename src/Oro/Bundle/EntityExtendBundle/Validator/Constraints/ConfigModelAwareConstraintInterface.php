<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;

/**
 * Declares that constraint is aware of config mode. Use to pass config model to validator.
 */
interface ConfigModelAwareConstraintInterface
{
    public function getConfigModel(): ConfigModel;
}
