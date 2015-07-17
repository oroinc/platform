<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\MailboxProcessorSettings;

interface MailboxProcessorInterface
{

    /**
     * Configures processor from processor entity.
     *
     * @param MailboxProcessorSettings $processor
     */
    public function configureFromEntity(MailboxProcessorSettings $processor);

    /**
     * Processes email and performs actions accordingly.
     *
     * @param EmailUser $emailUser
     */
    public function process(EmailUser $emailUser);

    /**
     * Returns processor type.
     *
     * @return string
     */
    public function getType();

    /**
     * Returns label of processor type.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Returns fully qualified class name of settings entity for this processor.
     *
     * @return string
     */
    public function getEntityFQCN();

    /**
     * Returns string identifier of form type that will be displayed for processor entity configuration.
     *
     * @return string
     */
    public function getSettingsFormType();
}
