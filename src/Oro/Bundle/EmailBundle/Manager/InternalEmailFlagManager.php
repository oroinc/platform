<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerInterface;

/**
 * Provides a set of methods to manage flags for internal email messages.
 */
class InternalEmailFlagManager implements EmailFlagManagerInterface
{
    private const string FLAG_UNSEEN = 'UNSEEN';
    private const string FLAG_SEEN = 'SEEN';

    #[\Override]
    public function setFlags(EmailFolder $folder, Email $email, array $flags): void
    {
        /**
         * Do nothing, because we do not need any additional actions
         * for setting flags SEEN\UNSEEN for InternalEmailOrigin
         */
    }

    #[\Override]
    public function setUnseen(EmailFolder $folder, Email $email): void
    {
        $this->setFlags($folder, $email, [self::FLAG_UNSEEN]);
    }

    #[\Override]
    public function setSeen(EmailFolder $folder, Email $email): void
    {
        $this->setFlags($folder, $email, [self::FLAG_SEEN]);
    }
}
