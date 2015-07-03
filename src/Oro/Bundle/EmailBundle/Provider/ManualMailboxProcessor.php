<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\MailboxProcessor;
use Oro\Bundle\EmailBundle\Entity\ManualMailboxProcessor as ProcessorEntity;

class ManualMailboxProcessor implements MailboxProcessorInterface
{

    /**
     * {@inheritdoc}
     */
    public function configureFromEntity(MailboxProcessor $processor)
    {
        // TODO: Implement configureFromEntity() method.
    }

    /**
     * {@inheritdoc}
     */
    public function process(Email $email)
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return ProcessorEntity::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.email.mailbox_processor.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityFQCN()
    {
        return 'Oro\Bundle\EmailBundle\Entity\ManualMailboxProcessor';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return 'oro_email_mailbox_processor_manual';
    }
}
