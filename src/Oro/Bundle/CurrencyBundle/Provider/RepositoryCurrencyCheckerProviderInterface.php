<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Defines the contract for providers that check currency usage in entity repositories.
 *
 * Implement this interface to create providers that can verify whether specific currencies
 * are being used by entities in the database. This is particularly useful for preventing
 * the removal of currencies that are actively referenced by existing records, ensuring
 * data integrity when managing the system's currency configuration.
 */
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
        ?Organization $organization = null
    );
}
