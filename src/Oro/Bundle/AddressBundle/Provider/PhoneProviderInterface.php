<?php

namespace Oro\Bundle\AddressBundle\Provider;

/**
 * Defines the contract for retrieving phone numbers from objects.
 *
 * Implementations of this interface provide the ability to extract phone number
 * information from various entity types. Providers can return a single primary phone
 * number or a complete list of all available phone numbers associated with an object,
 * along with information about the phone owner.
 */
interface PhoneProviderInterface
{
    /**
     * Gets a phone number of the given object
     *
     * @param object $object
     *
     * @return string|null
     */
    public function getPhoneNumber($object);

    /**
     * Gets a list of all phone numbers available for the given object
     *
     * @param object $object
     *
     * @return array of [phone number, phone owner]
     */
    public function getPhoneNumbers($object);
}
