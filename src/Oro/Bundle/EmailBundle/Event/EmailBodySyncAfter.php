<?php

namespace Oro\Bundle\EmailBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Oro\Bundle\EmailBundle\Entity\Email;

class EmailBodySyncAfter extends Event
{
    const NAME = 'oro_email.email_cache_manager.email_body_sync.after';

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
