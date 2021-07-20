<?php

namespace Oro\Bundle\UserBundle\Mailer;

use Oro\Bundle\EmailBundle\Manager\EmailTemplateManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Sends template email to the specified user.
 */
class UserTemplateEmailSender
{
    /**
     * @var NotificationSettings
     */
    private $notificationSettingsModel;

    /**
     * @var EmailTemplateManager
     */
    private $emailTemplateManager;

    public function __construct(
        NotificationSettings $notificationSettingsModel,
        EmailTemplateManager $emailTemplateManager
    ) {
        $this->notificationSettingsModel = $notificationSettingsModel;
        $this->emailTemplateManager = $emailTemplateManager;
    }

    /**
     * @param UserInterface $user
     * @param string $emailTemplateName
     * @param array $emailTemplateParams
     * @param $scopeEntity
     * @return int
     */
    public function sendUserTemplateEmail(
        UserInterface $user,
        $emailTemplateName,
        array $emailTemplateParams = [],
        $scopeEntity = null
    ): int {
        $from = $scopeEntity
            ? $this->notificationSettingsModel->getSenderByScopeEntity($scopeEntity)
            : $this->notificationSettingsModel->getSender();

        return $this->emailTemplateManager->sendTemplateEmail(
            $from,
            [$user],
            new EmailTemplateCriteria($emailTemplateName),
            $emailTemplateParams
        );
    }
}
