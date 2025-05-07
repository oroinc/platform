<?php

namespace Oro\Bundle\EntityConfigBundle\Validator;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\Provider\FieldConfigConstraintsProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Creates constraints based on field configuration.
 */
class FieldConfigConstraintsFactory
{
    public function __construct(
        private readonly ContainerInterface $providers
    ) {
    }

    /**
     * Creates a validation constraints.
     *
     * @return Constraint[]
     */
    public function create(ConfigInterface $config): array
    {
        $fieldType = $config->getId()->getFieldType();
        if (!$this->providers->has($fieldType)) {
            return [];
        }
        $provider = $this->providers->get($fieldType);
        if (!$provider instanceof FieldConfigConstraintsProviderInterface) {
            return [];
        }

        return $provider->create($config);
    }
}
