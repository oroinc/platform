<?php

namespace Oro\Bundle\EmailBundle\Mail\Header;

use Zend\Mail\AddressList;

/**
 * The email address list that ignores empty address.
 */
class OptionalAddressList extends AddressList
{
    /**
     * {@inheritdoc}
     */
    public function addFromString($address)
    {
        if (empty($address)) {
            return $this;
        }

        return parent::addFromString($address);
    }
}
