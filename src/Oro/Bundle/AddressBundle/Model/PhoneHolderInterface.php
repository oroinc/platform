<?php

namespace Oro\Bundle\AddressBundle\Model;

/**
 * Represents a subject which may provide phone numbers
 */
interface PhoneHolderInterface
{
    /**
     * Gets a primary phone number of entity
     *
     * @return string
     */
    public function getPrimaryPhoneNumber();

    /**
     * Gets list of entity phone numbers
     *
     * @return array
     */
    public function getPhoneNumbers();
}
