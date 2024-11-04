<?php

namespace Oro\Bundle\ImapBundle\Mail\Header;

use Laminas\Mail\AddressList;

/**
 * The email address list that ignores empty address.
 */
class OptionalAddressList extends AddressList
{
    #[\Override]
    public function addFromString($address, $comment = null)
    {
        if (empty($address)) {
            return $this;
        }

        return parent::addFromString($address);
    }
}
