<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Provider;

use Oro\Bundle\NavigationBundle\Menu\Provider\AbstractOwnershipProvider;

class OwnershipProviderStub extends AbstractOwnershipProvider
{
    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return 'stub_type';
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return 34;
    }
}
