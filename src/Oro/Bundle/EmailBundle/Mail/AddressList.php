<?php
/**
 * This file is a copy of {@see Zend\Mail\AddressList}
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Oro\Bundle\EmailBundle\Mail;

use \Zend\Mail\AddressList as BaseAddressList;

class AddressList extends BaseAddressList
{
    /**
     * Create an address object
     *
     * @param  string      $email
     * @param  string|null $name
     *
     * @return \Oro\Bundle\EmailBundle\Mail\Address
     */
    protected function createAddress($email, $name)
    {
        return new Address($email, $name);
    }
}
