<?php

namespace Oro\Bundle\EmailBundle\Mailbox;


use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Provider\MailboxProcessorProvider;

class MailboxEmailProcessor
{
    /** @var MailboxProcessorProvider */
    private $mailboxProcessorProvider;

    /**
     * MailboxEmailProcessor constructor.
     *
     * @param MailboxProcessorProvider $mailboxProcessorProvider
     */
    public function __construct(MailboxProcessorProvider $mailboxProcessorProvider)
    {
        $this->mailboxProcessorProvider = $mailboxProcessorProvider;
    }

    /**
     * @param EmailUser $emailUser
     */
    public function process(EmailUser $emailUser)
    {
        $processor = $this->mailboxProcessorProvider->getProcessor($emailUser->getMailboxOwner()->getProcessor());

        return $processor->process($emailUser);
    }
}
