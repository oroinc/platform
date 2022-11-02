<?php

namespace Oro\Bundle\EntityConfigBundle\Helper;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ConfigModelAwareConstraintInterface;

/**
 * Pass ConfigModel instance to supporting constrains configurations.
 */
class ConfigModelConstraintsHelper
{
    public static function configureConstraintsWithConfigModel(array $constraints, ConfigModel $configModel): array
    {
        foreach ($constraints as &$constraintData) {
            foreach ($constraintData as $constraint => &$constraintConfig) {
                if (is_a($constraint, ConfigModelAwareConstraintInterface::class, true)) {
                    $constraintConfig['configModel'] = $configModel;
                }
            }
            unset($constraintConfig);
        }
        unset($constraintData);

        return $constraints;
    }
}
