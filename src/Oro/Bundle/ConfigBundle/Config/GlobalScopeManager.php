<?php

namespace Oro\Bundle\ConfigBundle\Config;

/**
 * Global config scope
 */
class GlobalScopeManager extends AbstractScopeManager
{
    public const SCOPE_NAME = 'app';

    public function getScopedEntityName(): string
    {
        return self::SCOPE_NAME;
    }

    public function getScopeId(): ?int
    {
        return 0;
    }

    public function getScopeIdFromEntity($entity): int
    {
        return 0;
    }
}
