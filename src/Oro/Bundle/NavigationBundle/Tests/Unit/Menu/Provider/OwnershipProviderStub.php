<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Provider;

use Oro\Bundle\NavigationBundle\Menu\Provider\AbstractOwnershipProvider;

// TODO: remove this class in ticket BB-5468
class OwnershipProviderStub extends AbstractOwnershipProvider
{
    const TYPE = 'stub_type';

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
        return 34;
    }
}
