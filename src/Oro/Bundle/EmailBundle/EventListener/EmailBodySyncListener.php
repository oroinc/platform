<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Event\EmailBodySyncAfter;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;

class EmailBodySyncListener
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
     * @param EmailBodySyncAfter $event
     */
    public function linkToScopeEvent(EmailBodySyncAfter $event)
    {
        $email = $event->getEmail();
        if ((bool)$this->configManager->get(self::LINK_ATTACHMENT_CONFIG_OPTION)) {
            $this->attachmentManager->linkEmailAttachmentsToTargetEntities($email);
        }
    }
}
