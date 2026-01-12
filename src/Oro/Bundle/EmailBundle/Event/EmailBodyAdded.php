<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Entity\Email;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when an email body is added to an email.
 *
 * This event is triggered after an email body has been successfully added or loaded,
 * allowing listeners to perform additional processing on the email with its body content.
 */
class EmailBodyAdded extends Event
{
    public const NAME = 'oro_email.email_body_added';

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
