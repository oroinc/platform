<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface DefaultOwnerTypeAwareInterface
{
    const USER          = 'user';
    const BUSINESS_UNIT = 'business_unit';

    /**
     * Returns default owner type for entities created by this integration.
     *
     * @return string
     */
    public function getDefaultOwnerType();
}
