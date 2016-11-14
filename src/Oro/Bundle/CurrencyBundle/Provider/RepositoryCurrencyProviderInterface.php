<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

interface RepositoryCurrencyProviderInterface
{
    /**
     * Return translated label of entity
     *
     * @return string
     */
    public function getEntityLabel();

    /**
     * @param array             $availableCurrencies
     * @param Organization|null $organization
     *
     * @return bool
     */
    public function hasRecordsInUnavailableCurrencies(
        array $availableCurrencies,
        Organization $organization = null
    );
}
