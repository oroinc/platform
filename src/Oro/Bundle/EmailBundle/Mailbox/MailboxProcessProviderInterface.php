<?php

namespace Oro\Bundle\EmailBundle\Mailbox;

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
     * @return bool
     */
    public function isEnabled();
}
