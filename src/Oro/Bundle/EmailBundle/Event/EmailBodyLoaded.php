<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Entity\Email;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when an email body is loaded from storage.
 *
 * This event is triggered after an email body has been successfully retrieved from the database,
 * allowing listeners to perform additional processing or validation on the loaded email body.
 */
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
