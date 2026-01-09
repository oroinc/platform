<?php

namespace Oro\Bundle\OrganizationBundle\Provider;

use Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

/**
 * Provides phone number information for business unit entities.
 *
 * This provider implements the {@see PhoneProviderInterface} to extract phone numbers from
 * {@see BusinessUnit} entities. It retrieves the primary phone number and can provide a list
 * of all available phone numbers associated with a business unit.
 */
class BusinessUnitPhoneProvider implements PhoneProviderInterface
{
    /**
     * Gets a phone number of the given BusinessUnit object
     *
     * @param BusinessUnit $object
     *
     * @return string|null
     */
    #[\Override]
    public function getPhoneNumber($object)
    {
        return $object->getPhone();
    }

    /**
     * Gets a list of all phone numbers available for the given BusinessUnit object
     *
     * @param BusinessUnit $object
     *
     * @return array of [phone number, phone owner]
     */
    #[\Override]
    public function getPhoneNumbers($object)
    {
        $result = [];

        $phone = $object->getPhone();
        if (!empty($phone)) {
            $result[] = [$phone, $object];
        }

        return $result;
    }
}
