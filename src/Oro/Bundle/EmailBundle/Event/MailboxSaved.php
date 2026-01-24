<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a mailbox is saved.
 *
 * This event is triggered after a mailbox configuration has been successfully saved,
 * allowing listeners to perform additional processing such as synchronization or validation.
 */
class MailboxSaved extends Event
{
    const NAME = 'oro_email.mailbox_saved';

    /** @var Mailbox */
    protected $mailbox;

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
