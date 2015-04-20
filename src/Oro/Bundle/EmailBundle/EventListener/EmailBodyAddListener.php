<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailBodyAddListener
{
    const LINK_ATTACHMENT_CONFIG_OPTION = 'auto_link_attachments';

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var EmailAttachmentManager */
    protected $attachmentManager;

    /** @var EmailActivityListProvider */
    protected $activityListProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param EmailAttachmentManager $attachmentManager
     * @param ConfigProvider $configProvider
     * @param EmailActivityListProvider $activityListProvider
     * @param ServiceLink $securityFacadeLink
     */
    public function __construct(
        EmailAttachmentManager $attachmentManager,
        ConfigProvider $configProvider,
        EmailActivityListProvider $activityListProvider,
        ServiceLink $securityFacadeLink
    ) {
        $this->attachmentManager = $attachmentManager;
        $this->configProvider = $configProvider;
        $this->activityListProvider = $activityListProvider;
        $this->securityFacade = $securityFacadeLink->getService();
    }

    /**
     * @param EmailBodyAdded $event
     */
    public function linkToScopeEvent(EmailBodyAdded $event)
    {
        if (
            $this->securityFacade->getToken() !== null
            && !$this->securityFacade->isGranted('CREATE', 'entity:' . AttachmentScope::ATTACHMENT)
        ) {
            return;
        }
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
