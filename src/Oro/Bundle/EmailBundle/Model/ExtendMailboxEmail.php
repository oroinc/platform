<?php

namespace Oro\Bundle\EmailBundle\Model;


use Oro\Bundle\AddressBundle\Entity\AbstractEmail;

class ExtendMailboxEmail extends AbstractEmail
{
    /**
     * Constructor
     *
     * The real implementation of this method is auto generated.
     *
     * IMPORTANT: If the derived class has own constructor it must call parent constructor.
     *
     * @param string|null $email
     */
    public function __construct($email = null)
    {
        parent::__construct($email);
    }
}
