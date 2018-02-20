<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Symfony\Component\EventDispatcher\Event;

class EmailUserAdded extends Event
{
    const NAME = 'oro_email.email_user_added';

    /**
     * @var EmailUser
     */
    protected $emailUser;

    /**
     * @param EmailUser $emailUser
     */
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
