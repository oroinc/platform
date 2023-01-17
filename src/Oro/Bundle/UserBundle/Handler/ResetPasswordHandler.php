<?php

namespace Oro\Bundle\UserBundle\Handler;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Psr\Log\LoggerInterface;

/**
 * Responsible for resetting user's password, setting auth_status to reset and sending reset token to the user
 */
class ResetPasswordHandler
{
    private const TEMPLATE_NAME = 'force_reset_password';

    private EmailNotificationManager $mailManager;
    private UserManager $userManager;
    private LoggerInterface $logger;

    public function __construct(
        EmailNotificationManager $mailManager,
        UserManager $userManager,
        LoggerInterface $logger
    ) {
        $this->mailManager = $mailManager;
        $this->userManager = $userManager;
        $this->logger = $logger;
    }

    /**
     * Generates reset password confirmation token, block the user from login and sends notification email.
     * Skips disabled users
     */
    public function resetPasswordAndNotify(User $user): bool
    {
        if (!$user->isEnabled()) {
            return false;
        }

        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($user->generateToken());
        }

        $this->userManager->setAuthStatus($user, UserManager::STATUS_RESET);
        $this->userManager->updateUser($user);

        try {
            $this->mailManager->processSingle($this->getNotification($user), [], $this->logger);

            return true;
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->error(sprintf('Sending email to %s failed.', $user->getEmail()));
                $this->logger->error($e->getMessage());
            }
        }

        return false;
    }

    private function getNotification(User $user): TemplateEmailNotificationInterface
    {
        return new TemplateEmailNotification(
            new EmailTemplateCriteria(self::TEMPLATE_NAME, User::class),
            [$user],
            $user
        );
    }
}
