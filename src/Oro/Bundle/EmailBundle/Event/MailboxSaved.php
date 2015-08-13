<?php

namespace Oro\Bundle\EmailBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EmailBundle\Entity\Mailbox;

class MailboxSaved extends Event
{
    const NAME = 'oro_email.mailbox_saved';

    /** @var Mailbox */
    protected $mailbox;

    /**
     * @param Mailbox $mailbox
     */
    public function __construct(Mailbox $mailbox)
    {
        $this->mailbox = $mailbox;
    }

    /**
     * @return Mailbox
     */
    public function getMailbox()
    {
        return $this->mailbox;
    }
}
