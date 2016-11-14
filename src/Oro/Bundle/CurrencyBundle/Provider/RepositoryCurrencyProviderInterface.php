<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

interface RepositoryCurrencyProviderInterface
{
    /**
     * Returns list of unique currency codes from entity that contains multi-currency fields
     *
     * @param Organization|null $organization
     * @return string[]
     */
    public function getCurrencyList(Organization $organization = null);
}
