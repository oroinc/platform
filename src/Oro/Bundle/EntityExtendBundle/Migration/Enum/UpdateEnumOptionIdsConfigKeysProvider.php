<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Enum;

/**
 * This class is used to collect config key values that contains enum option IDs and required update.
 */
readonly class UpdateEnumOptionIdsConfigKeysProvider
{
    public function __construct(protected array $configKeys, protected string $enumCode)
    {
    }

    public function getConfigKeys(): array
    {
        return $this->configKeys;
    }

    public function getEnumCode(): string
    {
        return $this->enumCode;
    }
}
