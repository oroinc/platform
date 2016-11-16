<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

interface RepositoryCurrencyCheckerProviderInterface
{
    /**
     * Return entity label translation id
     *
     * @return string
     */
    public function getEntityLabel();

    /**
     * This method should return result of checking on existence
     * entity records that use currency that user want to remove.
     *
     * Pass no organization in case when user try to remove currency on system level.
     *
     * @param array             $removingCurrencies
     * @param Organization|null $organization
     *
     * @return bool
     */
    public function hasRecordsWithRemovingCurrencies(
        array $removingCurrencies,
        Organization $organization = null
    );
}
