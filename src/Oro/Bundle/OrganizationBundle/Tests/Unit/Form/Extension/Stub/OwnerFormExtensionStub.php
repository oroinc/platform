<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Form\Extension\OwnerFormExtension as BaseOwnerFormExtension;

class OwnerFormExtensionStub extends BaseOwnerFormExtension
{
    /**
     * {@inheritDoc}
     */
    protected function isBusinessUnitAvailableForCurrentUser(BusinessUnit $businessUnit)
    {
        return true;
    }
}
