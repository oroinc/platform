<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Doctrine\Common\Util\ClassUtils;

/**
 * The manager for configuration on global level.
 */
class GlobalScopeManager extends AbstractScopeManager
{
    public const SCOPE_NAME = 'app';

    #[\Override]
    public function getScopedEntityName(): string
    {
        return self::SCOPE_NAME;
    }

    #[\Override]
    public function getScopeIdFromEntity(object $entity): int
    {
        return 0;
    }

    #[\Override]
    public function getScopeId(): int
    {
        return 0;
    }

    #[\Override]
    public function setScopeId(?int $scopeId): void
    {
    }

    #[\Override]
    protected function isSupportedScopeEntity(object $entity): bool
    {
        return false;
    }

    #[\Override]
    protected function getScopeEntityIdValue(object $entity): int
    {
        throw new \LogicException(sprintf('"%s" is not supported.', ClassUtils::getClass($entity)));
    }
}
