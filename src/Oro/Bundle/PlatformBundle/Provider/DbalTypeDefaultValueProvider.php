<?php

namespace Oro\Bundle\PlatformBundle\Provider;

/**
 * Provides a value that can be used as default for the column of the specified DBAL type.
 */
class DbalTypeDefaultValueProvider
{
    private array $defaultValueByType = [
        'integer' => 0,
        'bigint' => 0,
        'smallint' => 0,
        'decimal' => 0.0,
        'float' => 0.0,
        'money' => 0.0,
        'percent' => 0,

        'string' => '',
        'text' => '',

        'boolean' => false,
    ];

    public function addDefaultValuesForDbalTypes(array $defaultValuesByTypes): void
    {
        $this->defaultValueByType = array_merge($this->defaultValueByType, $defaultValuesByTypes);
    }

    public function hasDefaultValueForDbalType(string $dbalType): bool
    {
        return array_key_exists($dbalType, $this->defaultValueByType);
    }

    public function getDefaultValueForDbalType(string $dbalType): mixed
    {
        if (!$this->hasDefaultValueForDbalType($dbalType)) {
            throw new \LogicException(sprintf('Default value is not specified for DBAL type %s', $dbalType));
        }


        return $this->defaultValueByType[$dbalType];
    }
}
