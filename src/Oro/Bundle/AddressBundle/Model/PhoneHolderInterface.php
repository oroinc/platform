<?php

namespace Oro\Bundle\AddressBundle\Model;

/**
 * Represents a subject which may be contacted by phone
 */
interface PhoneHolderInterface
{
    /**
     * Gets a primary phone number
     *
     * @return string|null
     */
    public function getPrimaryPhoneNumber();

    /**
     * Gets a list of all phone numbers
     *
     * @return string[]
     */
    public function getPhoneNumbers();
}
