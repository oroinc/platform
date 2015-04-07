<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EmailBodyAddListener
{
    const LINK_ATTACHMENT_CONFIG_OPTION = 'auto_link_attachments';

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var EmailAttachmentManager */
    protected $attachmentManager;

    /** @var EmailActivityListProvider */
    protected $activityListProvider;

    /**
     * @param EmailAttachmentManager $attachmentManager
     * @param ConfigProvider $configProvider
     * @param EmailActivityListProvider $activityListProvider
     */
    public function __construct(
        EmailAttachmentManager $attachmentManager,
        ConfigProvider $configProvider,
        EmailActivityListProvider $activityListProvider
    ) {
        $this->attachmentManager = $attachmentManager;
        $this->configProvider = $configProvider;
        $this->activityListProvider = $activityListProvider;
    }

    /**
     * @param EmailBodyAdded $event
     */
    public function linkToScopeEvent(EmailBodyAdded $event)
    {
        $email = $event->getEmail();
        $entities = $this->activityListProvider->getTargetEntities($email);
        foreach ($entities as $entity) {
            if ((bool)$this->configProvider->getConfig(ClassUtils::getClass($entity))->get('auto_link_attachments')) {
                foreach ($email->getEmailBody()->getAttachments() as $attachment) {
                    $this->attachmentManager->linkEmailAttachmentToTargetEntity($attachment, $entity);
                }
            }
        }
    }
}
