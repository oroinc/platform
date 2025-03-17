<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;

/**
 * Represents a service to manage flags for email messages.
 */
interface EmailFlagManagerInterface
{
    /**
     * Sets flags for message by EmailFolder and Email.
     */
    public function setFlags(EmailFolder $folder, Email $email, array $flags): void;

    /**
     * Sets flag UNSEEN for message by EmailFolder and Email.
     */
    public function setUnseen(EmailFolder $folder, Email $email): void;

    /**
     * Sets flag SEEN for message by EmailFolder and Email.
     */
    public function setSeen(EmailFolder $folder, Email $email): void;
}
