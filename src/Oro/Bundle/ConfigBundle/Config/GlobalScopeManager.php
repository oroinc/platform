<?php

namespace Oro\Bundle\ConfigBundle\Config;

/**
 * Global config scope
 */
class GlobalScopeManager extends AbstractScopeManager
{
    public const SCOPE_NAME = 'app';

    /**
     * {@inheritdoc}
     */
    public function getScopedEntityName()
    {
        return self::SCOPE_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeId()
    {
        return 0;
    }
}
