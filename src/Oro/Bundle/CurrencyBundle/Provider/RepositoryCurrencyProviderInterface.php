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
     * @param array             $currenciesOnRemove
     * @param Organization|null $organization
     *
     * @return bool
     */
    public function hasRecordsInCurrenciesOnRemove(
        array $currenciesOnRemove,
        Organization $organization = null
    );
}
