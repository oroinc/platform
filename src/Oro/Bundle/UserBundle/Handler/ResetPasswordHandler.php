<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Handler;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Event\PasswordChangeEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Responsible for resetting the user's password, setting auth_status to reset and sending a reset token to the user
 */
class ResetPasswordHandler
{
    public const string TEMPLATE_NAME = 'force_reset_password';

    public function __construct(
        protected readonly EmailNotificationManager $mailManager,
        protected readonly UserManager $userManager,
        protected readonly LoggerInterface $logger,
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {
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

        // Dispatch event to allow extensions to prevent password reset
        $event = new PasswordChangeEvent($user);
        $this->eventDispatcher->dispatch($event, PasswordChangeEvent::BEFORE_PASSWORD_RESET);
        if (!$event->isAllowed()) {
            $this->logger->error(\sprintf(
                'Admin password reset is not allowed for user %d (%s), reason: %s',
                $user->getId(),
                $user->getUsername(),
                $event->getNotAllowedLogMessage() ?? $event->getNotAllowedMessage() ?? 'Unspecified',
            ));
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
            $this->logger->error(sprintf('Sending email to %s failed.', $user->getEmail()));
            $this->logger->error($e->getMessage());
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
