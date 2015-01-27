<?php

namespace Oro\Bundle\ConfigBundle\Config;

class GlobalScopeManager extends AbstractScopeManager
{
    /**
     * {@inheritdoc}
     */
    public function getScopedEntityName()
    {
        return 'app';
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeId()
    {
        return 0;
    }
}
