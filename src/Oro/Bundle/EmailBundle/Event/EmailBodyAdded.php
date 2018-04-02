<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Entity\Email;
use Symfony\Component\EventDispatcher\Event;

class EmailBodyAdded extends Event
{
    const NAME = 'oro_email.email_body_added';

    /** @var Email */
    protected $email;

    /**
     * @param Email $email
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }
}
