<?php
/**
 * This file is a copy of {@see Zend\Mail\AddressList}
 *
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail;

use \Zend\Mail\AddressList as BaseAddressList;

/**
 * Overridden zend-mail AddressList that uses overridden Address class for addresses.
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
