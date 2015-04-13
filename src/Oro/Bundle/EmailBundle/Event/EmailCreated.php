<?php

namespace Oro\Bundle\EmailBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Oro\Bundle\EmailBundle\Entity\Email;

class EmailCreated extends Event
{
    const NAME = 'oro_email.email_created';

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
