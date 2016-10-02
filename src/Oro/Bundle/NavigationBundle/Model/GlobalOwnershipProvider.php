<?php

namespace Oro\Bundle\NavigationBundle\Model;

class GlobalOwnershipProvider implements OwnershipProviderInterface
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
