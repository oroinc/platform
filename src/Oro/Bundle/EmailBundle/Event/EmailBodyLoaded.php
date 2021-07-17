<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Entity\Email;
use Symfony\Contracts\EventDispatcher\Event;

class EmailBodyLoaded extends Event
{
    const NAME = 'oro_email.email_body_loaded';

    /** @var Email */
    protected $email;

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
