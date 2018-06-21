<?php

namespace Oro\Bundle\ImapBundle\Mail\Header;

use \Zend\Mail\AddressList;

/**
 * The Zend Framework zend-mail package provides more strictly rules for email headers.
 * To simplify checks they need to be overridden as the zend-mail is used only for import emails, and it is assumed
 * that if email exists on the mail server it has passed all checks and can be safety imported.
 *
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
