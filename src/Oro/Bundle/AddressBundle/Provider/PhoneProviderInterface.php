<?php

namespace Oro\Bundle\AddressBundle\Provider;

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
