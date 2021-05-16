<?php

namespace Oro\Bundle\EntityConfigBundle\Validator\Constraints\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Interface that all FieldConfigConstraintsProviders (tag oro_entity_config.field_config_constraints_provider)
 * should implement
 */
interface FieldConfigConstraintsProviderInterface
{
    /**
     * Creates validation constraints.
     *
     * @param ConfigInterface $config
     *
     * @return Constraint[]
     */
    public function create(ConfigInterface $config): array;
}
