<?php

namespace Oro\Bundle\UserBundle\Handler;

use Psr\Log\LoggerInterface;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\EmailNotification;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

/**
 * Responsible for resetting user's password, setting auth_status to expired and sending reset token to the user
 */
class ResetPasswordHandler
{
    const TEMPLATE_NAME = 'force_reset_password';

    /** @var EmailTemplate */
    protected $template;

    /** @var Registry */
    protected $registry;

    /**
     * @param EmailNotificationManager $mailManager
     * @param UserManager $userManager
     * @param Registry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(
        EmailNotificationManager $mailManager,
        UserManager $userManager,
        Registry $registry,
        LoggerInterface $logger
    ) {
        $this->mailManager = $mailManager;
        $this->userManager = $userManager;
        $this->template = null;
        $this->logger = $logger;
        $this->registry = $registry;
    }

    /**
     * Generates reset password confirmation token, block the user from login and sends notification email.
     * Skips disabled users
     *
     * @param User $user
     *
     * @return bool Notification success
     */
    public function resetPasswordAndNotify(User $user)
    {
        if (!$user->isEnabled()) {
            return false;
        }

        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($user->generateToken());
        }

        $this->userManager->setAuthStatus($user, UserManager::STATUS_EXPIRED);
        $this->userManager->updateUser($user);

        try {
            $this->mailManager->process($user, [$this->getNotification($user)], $this->logger);

            return true;
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->error(sprintf('Sending email to %s failed.', $user->getEmail()));
                $this->logger->error($e->getMessage());
            }
        }

        return false;
    }

    /**
     * @param User $user
     *
     * @return EmailNotification
     */
    protected function getNotification(User $user)
    {
        if (null === $this->template) {
            $this->template = $this->registry->getRepository(EmailTemplate::class)->findOneByName(self::TEMPLATE_NAME);
        }

        return new EmailNotification($this->template, [$user->getEmail()]);
    }
}
