<?php

namespace Oro\Bundle\EmailBundle\Mailbox;

use Oro\Bundle\EmailBundle\Entity\Mailbox;

interface MailboxProcessProviderInterface
{
    /**
     * Returns fully qualified class name of settings entity for this process.
     *
     * @return string
     */
    public function getSettingsEntityFQCN();

    /**
     * Returns form type used for settings entity used by this process.
     *
     * @return string
     */
    public function getSettingsFormType();

    /**
     * Returns id for translation which is used as label for this process type.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Returns true if process is enabled for given mailbox.
     *
     * @param Mailbox $mailbox
     *
     * @return bool
     */
    public function isEnabled(Mailbox $mailbox = null);
}
