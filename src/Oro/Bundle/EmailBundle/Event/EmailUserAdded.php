<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when an email user association is added.
 *
 * This event is triggered when a new email user record is created, allowing listeners
 * to perform additional processing such as updating related data or triggering workflows.
 */
class EmailUserAdded extends Event
{
    const NAME = 'oro_email.email_user_added';

    /**
     * @var EmailUser
     */
    protected $emailUser;

    public function __construct(EmailUser $emailUser)
    {
        $this->setEmailUser($emailUser);
    }

    /**
     * @return EmailUser
     */
    public function getEmailUser()
    {
        return $this->emailUser;
    }

    /**
     * @param EmailUser $emailUser
     *
     * @return $this
     */
    public function setEmailUser($emailUser)
    {
        $this->emailUser = $emailUser;

        return $this;
    }
}
