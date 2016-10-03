<?php

namespace Oro\Bundle\NavigationBundle\Menu\Provider;

class GlobalOwnershipProvider extends AbstractOwnershipProvider
{
    const TYPE = 'global';

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return 0;
    }
}
