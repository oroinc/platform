<?php

namespace Oro\Bundle\EmailBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EmailBundle\Entity\UserEmailOwner;

class EmailUserAdded extends Event
{
    const NAME = 'oro_email.email_user_added';

    /**
     * @var UserEmailOwner
     */
    protected $emailUser;

    /**
     * @param UserEmailOwner $emailUser
     */
    public function __construct(UserEmailOwner $emailUser)
    {
        $this->setEmailUser($emailUser);
    }

    /**
     * @return UserEmailOwner
     */
    public function getEmailUser()
    {
        return $this->emailUser;
    }

    /**
     * @param UserEmailOwner $emailUser
     *
     * @return $this
     */
    public function setEmailUser($emailUser)
    {
        $this->emailUser = $emailUser;

        return $this;
    }
}
