<?php

namespace Oro\Bundle\ImapBundle\Mail\Header;

use \Zend\Mail\AddressList;

/**
 * The email address list that ignores empty address.
 */
class OptionalAddressList extends AddressList
{
    /**
     * {@inheritdoc}
     *
     * Do not throw exception in case if $address is empty
     */
    public function addFromString($address, $comment = null)
    {
        if (empty($address)) {
            return $this;
        }

        return parent::addFromString($address);
    }
}
