<?php

namespace Oro\Bundle\EmailBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EmailBundle\Entity\Email;

class EmailBodyLoaded extends Event
{
    const NAME = 'oro_email.email_body_loaded';

    /** @var Email */
    protected $email;

    /**
     * @param Email $email
     */
    public function __construct(Email $email)
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
