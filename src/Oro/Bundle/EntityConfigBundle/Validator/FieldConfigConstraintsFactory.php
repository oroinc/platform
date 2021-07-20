<?php

namespace Oro\Bundle\EntityConfigBundle\Validator;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\Provider\FieldConfigConstraintsProviderInterface;

/**
 * Creates constraints based on field configuration
 */
class FieldConfigConstraintsFactory
{
    /** @var array|FieldConfigConstraintsProviderInterface[] */
    private array $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = iterator_to_array($providers);
    }

    /**
     * Creates a validation constraints.
     */
    public function create(ConfigInterface $config): array
    {
        $fieldType = $config->getId()->getFieldType();
        $constraints = [];
        if (isset($this->providers[$fieldType]) &&
            $this->providers[$fieldType] instanceof FieldConfigConstraintsProviderInterface
        ) {
            $constraints = $this->providers[$fieldType]->create($config);
        }

        return $constraints;
    }
}
