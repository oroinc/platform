<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;

class EmailBodyAddListener
{
    const LINK_ATTACHMENT_CONFIG_OPTION = 'oro_email.link_email_attachments_to_scope_entity';

    /** @var ConfigManager */
    protected $configManager;

    /** @var EmailAttachmentManager */
    protected $attachmentManager;

    public function __construct(
        EmailAttachmentManager $attachmentManager,
        ConfigManager $configManager
    ) {
        $this->attachmentManager = $attachmentManager;
        $this->configManager = $configManager;
    }

    /**
     * @param EmailBodyAdded $event
     */
    public function linkToScopeEvent(EmailBodyAdded $event)
    {
        $email = $event->getEmail();
        if ((bool)$this->configManager->get(self::LINK_ATTACHMENT_CONFIG_OPTION)) {
            $this->attachmentManager->linkEmailAttachmentsToTargetEntities($email);
        }
    }
}
