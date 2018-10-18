<?php

namespace Oro\Bundle\UserBundle\Mailer;

use Oro\Bundle\EmailBundle\Manager\TemplateEmailManager;
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
     * @var TemplateEmailManager
     */
    private $templateEmailManager;

    /**
     * @param NotificationSettings $notificationSettingsModel
     * @param TemplateEmailManager $templateEmailManager
     */
    public function __construct(
        NotificationSettings $notificationSettingsModel,
        TemplateEmailManager $templateEmailManager
    ) {
        $this->notificationSettingsModel = $notificationSettingsModel;
        $this->templateEmailManager = $templateEmailManager;
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

        return $this->templateEmailManager->sendTemplateEmail(
            $from,
            [$user],
            new EmailTemplateCriteria($emailTemplateName),
            $emailTemplateParams
        );
    }
}
