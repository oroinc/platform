<?php
/**
 * This file is a copy of {@see Zend\Mail\AddressList}
 *
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail;

use \Zend\Mail\AddressList as BaseAddressList;

/**
 * The Zend Framework zend-mail package provides more strictly rules for email headers.
 * To simplify checks they need to be overridden as the zend-mail is used only for import emails, and it is assumed
 * that if email exists on the mail server it has passed all checks and can be safety imported.
 */
class AddressList extends BaseAddressList
{
    /**
     * {@inheritdoc}
     *
     * This method is a copy of {@see Zend\Mail\AddressList::createAddress}
     * It is needed to override `new Address($email, $name)`
     */
    protected function createAddress($email, $name)
    {
        return new Address($email, $name);
    }
}
