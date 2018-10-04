<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerInterface;

/**
 * Set flags SEEN\UNSEEN for InternalEmailOrigin
 */
class InternalEmailFlagManager implements EmailFlagManagerInterface
{
    const FLAG_UNSEEN = 'UNSEEN';
    const FLAG_SEEN = 'SEEN';

    /**
     * {@inheritdoc}
     */
    public function setFlags(EmailFolder $folder, Email $email, $flags)
    {
        /**
         * Do nothing, because we do not need any additional actions
         * for setting flags SEEN\UNSEEN for InternalEmailOrigin
         */
    }

    /**
     * {@inheritdoc}
     */
    public function setUnseen(EmailFolder $folder, Email $email)
    {
        $this->setFlags($folder, $email, [self::FLAG_UNSEEN]);
    }

    /**
     * {@inheritdoc}
     */
    public function setSeen(EmailFolder $folder, Email $email)
    {
        $this->setFlags($folder, $email, [self::FLAG_SEEN]);
    }
}
