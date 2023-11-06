<?php

namespace Oro\Bundle\ConfigBundle\Config\ApiTree;

/**
 * The definition of a variable in API configuration tree.
 */
class VariableDefinition
{
    private string $key;
    private string $type;

    public function __construct(string $key, string $type)
    {
        $this->key = $key;
        $this->type = $type;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function toArray(): array
    {
        return [
            'key'  => $this->key,
            'type' => $this->type
        ];
    }
}
