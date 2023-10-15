<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Doctrine\Common\Util\ClassUtils;

/**
 * The manager for configuration on global level.
 */
class GlobalScopeManager extends AbstractScopeManager
{
    public const SCOPE_NAME = 'app';

    /**
     * {@inheritDoc}
     */
    public function getScopedEntityName(): string
    {
        return self::SCOPE_NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getScopeIdFromEntity(object $entity): int
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getScopeId(): int
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function setScopeId(?int $scopeId): void
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function isSupportedScopeEntity(object $entity): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function getScopeEntityIdValue(object $entity): int
    {
        throw new \LogicException(sprintf('"%s" is not supported.', ClassUtils::getClass($entity)));
    }
}
